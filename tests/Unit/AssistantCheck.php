<?php
// Standalone check (no Composer needed): php tests/Unit/AssistantCheck.php
require __DIR__.'/../../app/Services/AssistantService.php';

use App\Services\AssistantService as A;

$fail = 0;
$t = function (bool $ok, string $msg) use (&$fail) { echo ($ok ? 'PASS' : 'FAIL')."  $msg\n"; $fail += $ok ? 0 : 1; };

$t(A::tokens('How do I send a correction?') === ['correction'], 'filler words are dropped');
$t(in_array('event', A::tokens('Upcoming EVENTS!'), true), 'case and plurals are normalized');
$t(A::tokens('???') === [], 'punctuation-only gives no tokens');
$t(A::tokens('sports sports sports') === ['sport'], 'duplicates collapse');
$t(in_array('ñandú', A::tokens('Ñandú news'), true), 'accented letters are kept');

$s = A::scrub('My email is ana@example.com and my phone is +63 912 345 6789, help');
$t(! str_contains($s, 'ana@') && ! str_contains($s, '912') && str_contains($s, '[email]') && str_contains($s, '[number]'), 'emails and phone numbers are scrubbed from stored questions');
$t(mb_strlen(A::scrub(str_repeat('x', 500))) === 200, 'stored questions are capped at 200 characters');
$t(A::scrub('When is the next issue?') === 'When is the next issue?', 'ordinary questions are left alone');
exit($fail ? 1 : 0);
