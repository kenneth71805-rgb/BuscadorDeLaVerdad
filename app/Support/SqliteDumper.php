<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class SqliteDumper extends DatabaseDumper
{
    public function __construct(private string $connection)
    {
    }

    public function filename(): string
    {
        return 'database.sqlite';
    }

    public function dump(string $to): void
    {
        $source = (string) config("database.connections.{$this->connection}.database");
        if ($source === ':memory:' || ! is_file($source)) {
            throw new RuntimeException('The SQLite database is not a file, so it cannot be backed up.');
        }

        try {
            // A consistent snapshot even while the site is in use.
            DB::connection($this->connection)->statement("VACUUM INTO '".str_replace("'", "''", $to)."'");
        } catch (\Throwable) {
            if (! copy($source, $to)) {
                throw new RuntimeException('Could not copy the SQLite database file.');
            }
        }
    }
}
