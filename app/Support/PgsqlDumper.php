<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class PgsqlDumper extends DatabaseDumper
{
    public function __construct(private string $connection)
    {
    }

    public function filename(): string
    {
        return 'database.sql';
    }

    public function dump(string $to): void
    {
        $c = config("database.connections.{$this->connection}");
        $process = new Process(['pg_dump', '--no-owner', '--host='.$c['host'], '--port='.$c['port'], '--username='.$c['username'], '--file='.$to, $c['database']], null, ['PGPASSWORD' => (string) $c['password']]);
        $process->setTimeout(900);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException('pg_dump failed: '.trim($process->getErrorOutput() ?: 'is pg_dump installed and on the PATH?'));
        }
    }
}
