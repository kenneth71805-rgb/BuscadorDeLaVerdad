<?php

namespace App\Support;

use App\Exceptions\InvalidPdf;

/**
 * Basic safety checks for uploaded publication PDFs. The file is judged by its contents, never its name.
 *
 * This is a screening step, not a malware scanner: PDFs that hide active content inside compressed
 * streams can pass. PDFs are therefore always served as downloads (never rendered by the site),
 * with nosniff, and only to people who open them deliberately. Run an antivirus scan in production.
 */
class PdfValidator
{
    public const MAX_BYTES = 40 * 1024 * 1024;

    /** Names that make a PDF run code, launch programs, or carry hidden files. */
    private const ACTIVE = '~/(JavaScript|JS|Launch|EmbeddedFile|RichMedia)(?![A-Za-z0-9])~';

    /** @throws InvalidPdf */
    public static function assertSafe(string $path): void
    {
        $size = @filesize($path);
        if (! $size || $size > self::MAX_BYTES) {
            throw new InvalidPdf('The PDF must be larger than 0 bytes and no bigger than 40 MB.');
        }

        $handle = @fopen($path, 'rb');
        if (! $handle) {
            throw new InvalidPdf('The file could not be read.');
        }

        try {
            if (fread($handle, 5) !== '%PDF-') {
                throw new InvalidPdf('This does not look like a PDF file.');
            }

            fseek($handle, max(0, $size - 2048));
            if (! str_contains((string) fread($handle, 2048), '%%EOF')) {
                throw new InvalidPdf('The PDF looks incomplete or damaged. Please export it again.');
            }

            // Scan in chunks, overlapping so a keyword split across two chunks is still caught.
            rewind($handle);
            $tail = '';
            while (! feof($handle)) {
                $chunk = $tail.(string) fread($handle, 1024 * 1024);
                if (preg_match(self::ACTIVE, $chunk)) {
                    throw new InvalidPdf('This PDF contains scripts or embedded files and cannot be accepted. Export a plain PDF instead.');
                }
                $tail = substr($chunk, -32);
            }
        } finally {
            fclose($handle);
        }

        if (function_exists('finfo_open')) {
            $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
            if ($mime !== 'application/pdf') {
                throw new InvalidPdf('This does not look like a PDF file.');
            }
        }
    }
}
