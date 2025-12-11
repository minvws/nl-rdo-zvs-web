<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\User\UserCreateAction;
use App\Enums\Authorization\GlobalRole;
use Illuminate\Console\Command;
use Laravel\Prompts\TextPrompt;
use Throwable;
use Webmozart\Assert\Assert;

class CreateAdminUserCommand extends Command
{
    /** @var string $signature */
    protected $signature = 'app:create-admin-user';

    /** @var string $description */
    protected $description = 'Create an admin user for the application';

    public function handle(
        UserCreateAction $userCreateAction,
    ): int {
        $name = $this->askName();
        $email = $this->askEmail();

        $userCreate = [
            'name' => $name,
            'email' => $email,
            'global_roles' => [GlobalRole::ADMINISTRATOR->value],
            'department_roles' => [],
        ];

        try {
            $userCreateAction->execute($userCreate);
        } catch (Throwable) {
            $this->output->error('Admin user creation failed');

            return self::FAILURE;
        }

        $this->output->success('Admin user created');

        return self::SUCCESS;
    }

    private function askName(): string
    {
        $textPrompt = new TextPrompt(
            label: 'Name',
            default: 'admin',
            validate: [
                'string',
                'required',
                'max:255',
            ],
        );

        $prompt = $textPrompt->prompt();
        Assert::string($prompt);

        return $prompt;
    }

    private function askEmail(): string
    {
        $textPrompt = new TextPrompt(
            label: 'Email',
            default: 'admin@minvws.nl',
            validate: [
                'email',
                'required',
                'unique:users,email',
                'max:255',
            ],
        );

        $prompt = $textPrompt->prompt();
        Assert::string($prompt);

        return $prompt;
    }
}
