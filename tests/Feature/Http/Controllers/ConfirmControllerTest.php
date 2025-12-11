<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

use function __;
use function confirmRoute;

class ConfirmControllerTest extends FeatureTestCase
{
    public function testConfirmActionShows(): void
    {
        Department::factory()->create();

        $confirmUrl = 'https://example.com/confirm-action';
        $cancelUrl = 'https://example.com/cancel-action';

        $user = User::factory()->fullyVerified()->create();
        $response = $this->beUser($user)
            ->get(
                confirmRoute(
                    $confirmUrl,
                    $cancelUrl,
                    'confirm',
                ),
            );

        $response->assertSee(__('general.yes'));
    }
}
