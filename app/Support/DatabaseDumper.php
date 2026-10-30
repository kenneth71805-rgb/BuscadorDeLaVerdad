<?php

namespace App\Support;

use RuntimeException;

abstract class DatabaseDumper
{
    /** File name used inside the backup archive. */
    abstract public function filename(): string;

    abstract public function dump(string $to): void;

    public static function forDefault(): self
    {
        $name = config('database.default');
        $driver = config("database.connections.{$name}.driver");

        return match ($driver) {
            'sqlite' => new SqliteDumper($name),
            'mysql', 'mariadb' => new MysqlDumper($name),
            'pgsql' => new PgsqlDumper($name),
            default => throw new RuntimeException("Backups are not supported for the \"{$driver}\" database."),
        };
    }
}
