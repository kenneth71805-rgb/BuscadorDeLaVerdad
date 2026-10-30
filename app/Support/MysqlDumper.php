<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\Process\Process;

class MysqlDumper extends DatabaseDumper
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
        $process = new Process(
            ['mysqldump', '--single-transaction', '--quick', '--no-tablespaces', '--host='.$c['host'], '--port='.$c['port'], '--user='.$c['username'], $c['database']],
            null,
            ['MYSQL_PWD' => (string) $c['password']], // passed through the environment, never on the command line
        );
        $process->setTimeout(900);

        $out = fopen($to, 'wb');
        $process->run(function ($type, $buffer) use ($out) {
            if ($type === Process::OUT) {
                fwrite($out, $buffer);
            }
        });
        fclose($out);

        if (! $process->isSuccessful()) {
            throw new RuntimeException('mysqldump failed: '.trim($process->getErrorOutput() ?: 'is mysqldump installed and on the PATH?'));
        }
    }
}
