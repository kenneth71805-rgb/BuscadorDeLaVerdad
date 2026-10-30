<?php

namespace App\Services;

use App\Support\DatabaseDumper;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

/** Creates one .zip holding the database plus every uploaded photo and PDF, then removes old archives. */
class BackupRunner
{
    /** Folders of the private disk that hold uploaded content. */
    private const CONTENT = ['media', 'issues'];

    public function __construct(private DatabaseDumper $dumper)
    {
    }

    public function create(): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('The PHP zip extension is required for backups.');
        }

        $dir = rtrim((string) config('security.backup_path'), '/\\');
        if (! is_dir($dir) && ! @mkdir($dir, 0750, true) && ! is_dir($dir)) {
            throw new RuntimeException("Could not create the backup folder: {$dir}");
        }

        $path = $this->freshPath($dir);
        $partial = $path.'.partial'; // built under a temporary name; it only becomes a real backup once verified
        $dump = tempnam(sys_get_temp_dir(), 'dbdump');

        try {
            $this->dumper->dump($dump);
            if (! is_file($dump) || filesize($dump) === 0) {
                throw new RuntimeException('The database export is empty.');
            }

            $zip = new ZipArchive;
            if ($zip->open($partial, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Could not create the backup archive.');
            }

            $zip->addFile($dump, $this->dumper->filename());
            $files = $this->addContent($zip);
            $zip->addFromString('MANIFEST.txt', "Los Sueños backup\nCreated: ".now()->toIso8601String()."\nDatabase file: {$this->dumper->filename()}\nUploaded files: {$files}\n");
            $zip->close();

            $this->verify($partial, $this->dumper->filename());

            if (! rename($partial, $path)) {
                throw new RuntimeException('Could not save the finished backup.');
            }
        } catch (\Throwable $e) {
            @unlink($partial); // only ever removes the unfinished file, never an earlier good backup
            throw $e;
        } finally {
            @unlink($dump);
        }

        $this->prune();

        return $path;
    }

    private function freshPath(string $dir): string
    {
        $base = $dir.DIRECTORY_SEPARATOR.'backup-'.now()->format('Ymd-His');
        $path = $base.'.zip';
        for ($i = 2; file_exists($path); $i++) {
            $path = "{$base}-{$i}.zip"; // two backups in the same second never overwrite each other
        }

        return $path;
    }

    /** @return list<array{name:string,bytes:int,at:int}> newest first */
    public function list(): array
    {
        $files = glob(rtrim((string) config('security.backup_path'), '/\\').DIRECTORY_SEPARATOR.'backup-*.zip') ?: [];
        $rows = array_map(fn ($f) => ['name' => basename($f), 'bytes' => (int) filesize($f), 'at' => (int) filemtime($f)], $files);
        usort($rows, fn ($a, $b) => [$b['at'], $b['name']] <=> [$a['at'], $a['name']]);

        return $rows;
    }

    public function prune(?int $keep = null): int
    {
        $keep ??= max(1, (int) config('security.backup_keep'));
        $removed = 0;
        foreach (array_slice($this->list(), $keep) as $old) {
            if (@unlink(rtrim((string) config('security.backup_path'), '/\\').DIRECTORY_SEPARATOR.$old['name'])) {
                $removed++;
            }
        }

        return $removed;
    }

    private function addContent(ZipArchive $zip): int
    {
        $disk = Storage::disk('local');
        $count = 0;

        foreach (self::CONTENT as $folder) {
            foreach ($disk->allFiles($folder) as $file) {
                $zip->addFile($disk->path($file), 'files/'.$file);
                $count++;
            }
        }

        return $count;
    }

    private function verify(string $path, string $databaseName): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::CHECKCONS) !== true || $zip->locateName($databaseName) === false) {
            throw new RuntimeException('The finished backup failed its integrity check.');
        }
        $zip->close();
    }
}
