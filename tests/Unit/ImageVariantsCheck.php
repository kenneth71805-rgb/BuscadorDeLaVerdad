<?php
// Standalone check (no Composer needed): php tests/Unit/ImageVariantsCheck.php
require __DIR__.'/../../app/Exceptions/InvalidImage.php';
require __DIR__.'/../../app/Support/ImageVariants.php';

use App\Exceptions\InvalidImage;
use App\Support\ImageVariants;

$fail = 0;
$t = function (bool $ok, string $msg) use (&$fail) { echo ($ok ? 'PASS' : 'FAIL')."  $msg\n"; $fail += $ok ? 0 : 1; };
$tmp = sys_get_temp_dir().'/iv_'.getmypid();
@mkdir($tmp);

$rejects = function (string $file, string $why) use ($t) {
    try { ImageVariants::make($file); $t(false, "rejects $why"); }
    catch (InvalidImage $e) { $t(true, "rejects $why  (\"{$e->getMessage()}\")"); }
};

// A real, large photo-like JPEG
$img = imagecreatetruecolor(3000, 2000);
for ($i = 0; $i < 40; $i++) { imagefilledellipse($img, rand(0, 3000), rand(0, 2000), rand(50, 600), rand(50, 600), imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255))); }
imagejpeg($img, "$tmp/photo.jpg", 90);
$r = ImageVariants::make("$tmp/photo.jpg");
$t($r['width'] === 3000 && $r['height'] === 2000, 'reports original dimensions');
$t(array_keys($r['variants']) === ['thumb', 'medium', 'large', 'original'], 'produces all four variants');
$t($r['variants']['thumb']['width'] === 400 && $r['variants']['thumb']['height'] === 267, 'thumb is 400px wide, aspect kept');
$t($r['variants']['original']['width'] === 2400, 'original capped at 2400px');
$ok = true; foreach ($r['variants'] as $v) { $ok = $ok && str_starts_with($v['data'], "\xFF\xD8\xFF") && getimagesizefromstring($v['data'])[2] === IMAGETYPE_JPEG; }
$t($ok, 'every output is a fresh, valid JPEG');

// Small image is never enlarged
$small = imagecreatetruecolor(300, 200); imagejpeg($small, "$tmp/small.jpg");
$r = ImageVariants::make("$tmp/small.jpg");
$t($r['variants']['large']['width'] === 300 && $r['variants']['thumb']['width'] === 300, 'small image is not enlarged');

// PNG with transparency is flattened on white
$png = imagecreatetruecolor(100, 100); imagesavealpha($png, true); imagealphablending($png, false);
imagefill($png, 0, 0, imagecolorallocatealpha($png, 0, 0, 0, 127)); imagepng($png, "$tmp/t.png");
$r = ImageVariants::make("$tmp/t.png");
$out = imagecreatefromstring($r['variants']['original']['data']); $c = imagecolorat($out, 50, 50);
$t((($c >> 16) & 255) > 240 && (($c >> 8) & 255) > 240 && ($c & 255) > 240, 'transparent PNG becomes white, not black');

// Metadata is dropped: embed a marker in a JPEG comment + trailing data, check it is gone from output
$raw = file_get_contents("$tmp/photo.jpg"); file_put_contents("$tmp/tagged.jpg", $raw.'<?php SECRET_MARKER ?>');
$r = ImageVariants::make("$tmp/tagged.jpg");
$leak = false; foreach ($r['variants'] as $v) { $leak = $leak || str_contains($v['data'], 'SECRET_MARKER'); }
$t(! $leak, 'appended code/data does not survive re-encoding');

// Rejections
file_put_contents("$tmp/evil.jpg", '<?php echo shell_exec($_GET["c"]); ?>'); $rejects("$tmp/evil.jpg", 'PHP code named .jpg');
file_put_contents("$tmp/x.svg", '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'); $rejects("$tmp/x.svg", 'SVG with script');
$gif = imagecreatetruecolor(10, 10); imagegif($gif, "$tmp/a.gif"); $rejects("$tmp/a.gif", 'GIF');
file_put_contents("$tmp/empty.jpg", ''); $rejects("$tmp/empty.jpg", 'empty file');
file_put_contents("$tmp/trunc.jpg", substr($raw, 0, 200)); $rejects("$tmp/trunc.jpg", 'truncated/corrupt JPEG');
file_put_contents("$tmp/big.jpg", $raw.str_repeat('0', ImageVariants::MAX_BYTES)); $rejects("$tmp/big.jpg", 'file over 8 MB');
// Pixel bomb: tiny PNG file that declares huge dimensions
$bomb = imagecreatetruecolor(6000, 6000); imagepng($bomb, "$tmp/bomb.png", 9); $rejects("$tmp/bomb.png", '6000x6000 pixel image (over 24MP)');

array_map('unlink', glob("$tmp/*")); rmdir($tmp);
exit($fail ? 1 : 0);
