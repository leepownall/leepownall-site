<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('users:create')]
#[Description('Create a user who can sign in and connect AI clients to the MCP server')]
class CreateUserCommand extends Command
{
    public function handle(): int
    {
        $name = text('Name', required: true);
        $email = text('Email', required: true, validate: ['email' => ['email', 'unique:users,email']]);
        $password = password('Password', required: true, validate: ['password' => ['min:12']]);

        User::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);

        $this->components->info("Created {$email}.");

        return self::SUCCESS;
    }
}
