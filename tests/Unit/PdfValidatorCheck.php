<?php
// Standalone check (no Composer needed): php tests/Unit/PdfValidatorCheck.php
require __DIR__.'/../../app/Exceptions/InvalidPdf.php';
require __DIR__.'/../../app/Support/PdfValidator.php';

use App\Exceptions\InvalidPdf;
use App\Support\PdfValidator;

$fail = 0;
$t = function (bool $ok, string $msg) use (&$fail) { echo ($ok ? 'PASS' : 'FAIL')."  $msg\n"; $fail += $ok ? 0 : 1; };
$tmp = sys_get_temp_dir().'/pv_'.getmypid(); @mkdir($tmp);

function pdf(string $extra = ''): string {
    return "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R $extra >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n"
        ."3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
}
$accepts = function (string $name, string $content) use ($t, $tmp) {
    file_put_contents("$tmp/$name", $content);
    try { PdfValidator::assertSafe("$tmp/$name"); $t(true, "accepts $name"); } catch (InvalidPdf $e) { $t(false, "accepts $name ({$e->getMessage()})"); }
};
$rejects = function (string $name, string $content, string $why) use ($t, $tmp) {
    file_put_contents("$tmp/$name", $content);
    try { PdfValidator::assertSafe("$tmp/$name"); $t(false, "rejects $why"); } catch (InvalidPdf $e) { $t(true, "rejects $why  (\"{$e->getMessage()}\")"); }
};

$accepts('plain.pdf', pdf());
$accepts('page-open.pdf', pdf('/OpenAction [3 0 R /Fit]')); // ordinary "open at page" is fine
$rejects('text.pdf', "Hello, I am just text.\n%%EOF", 'text file named .pdf');
$rejects('php.pdf', "<?php system(\$_GET['c']); ?>\n%%EOF", 'PHP code named .pdf');
$rejects('zip.pdf', "PK\x03\x04".str_repeat('x', 100)."%%EOF", 'ZIP archive named .pdf');
$rejects('js.pdf', pdf('/Names << /JavaScript 9 0 R >>'), 'PDF with /JavaScript');
$rejects('js2.pdf', pdf('/AA << /O << /S /JS /JS (app.alert(1)) >> >>'), 'PDF with /JS action');
$rejects('launch.pdf', pdf('/OpenAction << /S /Launch /F (cmd.exe) >>'), 'PDF with /Launch');
$rejects('embed.pdf', pdf('/Names << /EmbeddedFiles 5 0 R >> /Type /EmbeddedFile'), 'PDF with embedded file');
$rejects('trunc.pdf', substr(pdf(), 0, 120), 'truncated PDF (no %%EOF)');
$rejects('empty.pdf', '', 'empty file');

// keyword straddling the 1 MB chunk boundary must still be caught
$pad = str_repeat(' ', 1024 * 1024 - 5);
$rejects('boundary.pdf', "%PDF-1.4\n".$pad.'/JavaScript'."\n%%EOF\n", 'keyword split across a read boundary');

// harmless names that merely start with "JS" must not be flagged
$accepts('jsonish.pdf', pdf('/JSONish true /Jsx 1'));

// size limit (sparse file, checked before reading)
$f = fopen("$tmp/huge.pdf", 'wb'); fwrite($f, "%PDF-1.4\n"); ftruncate($f, PdfValidator::MAX_BYTES + 10); fclose($f);
try { PdfValidator::assertSafe("$tmp/huge.pdf"); $t(false, 'rejects file over 40 MB'); } catch (InvalidPdf) { $t(true, 'rejects file over 40 MB'); }

array_map('unlink', glob("$tmp/*")); rmdir($tmp);
exit($fail ? 1 : 0);
