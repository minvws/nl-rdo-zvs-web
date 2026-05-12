<?php

declare(strict_types=1);

namespace App\Console\Commands\App;

use App\Actions\User\UserUpdateGlobalRolesAction;
use App\Enums\Authorization\GlobalRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Prompts\TextPrompt;
use Webmozart\Assert\Assert;

#[Signature('app:make-user-administrator')]
#[Description('Make a user admininstrator')]
class MakeUserAdministratorCommand extends Command
{
    public function handle(
        UserUpdateGlobalRolesAction $userUpdateGlobalRolesAction,
    ): int {
        $userEmail = $this->askEmail();

        try {
            $user = User::query()->where(['email' => $userEmail])->firstOrFail();
            $userUpdateGlobalRolesAction->execute($user, [GlobalRole::ADMINISTRATOR->value]);
        } catch (ModelNotFoundException) {
            $this->output->error('User not found');

            return self::FAILURE;
        }

        $this->output->success('Administrator role for user created');

        return self::SUCCESS;
    }

    private function askEmail(): string
    {
        $textPrompt = new TextPrompt(
            label: 'Give the user\'s email address',
            validate: [
                'email',
                'required',
                'max:255',
            ],
        );

        $prompt = $textPrompt->prompt();
        Assert::string($prompt);

        return $prompt;
    }
}
