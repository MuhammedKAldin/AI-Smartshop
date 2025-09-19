<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class MakeFilamentUser extends Command
{
    protected $signature = 'make:filament-user {--admin}';

    protected $description = 'Create a Filament user with optional admin flag';

    public function handle(): int
    {
        $name = $this->ask('Name');
        $email = $this->ask('Email address');
        $password = $this->secret('Password');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'is_admin' => $this->option('admin'), // set if --admin was passed
        ]);

        $this->info("Filament user {$user->email} created successfully!");

        return self::SUCCESS;
    }
}
