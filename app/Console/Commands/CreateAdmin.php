<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;

class CreateAdmin extends Command
{
    protected $signature = 'staff:create-admin {email} {name}';

    protected $description = 'Create an Admin account. The password is asked for interactively and never stored in code.';

    public function handle(): int
    {
        if (User::where('email', $this->argument('email'))->exists()) {
            $this->error('An account with that email already exists.');

            return self::FAILURE;
        }

        $plain = password(
            label: 'Password (min 12 characters)',
            required: true,
            validate: fn (string $v) => strlen($v) < 12 ? 'Use at least 12 characters.' : null,
        );

        $user = new User(['name' => $this->argument('name'), 'email' => $this->argument('email'), 'password' => $plain]);
        $user->forceFill(['role' => Role::Admin, 'email_verified_at' => now()])->save();
        AuditLog::record('staff.created', $user, ['role' => 'admin', 'via' => 'cli']);

        $this->info("Admin account created for {$user->email}.");

        return self::SUCCESS;
    }
}
