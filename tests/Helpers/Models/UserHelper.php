<?php

declare(strict_types=1);

namespace Tests\Helpers\Models;

use App\Enums\Authorization\DepartmentRole;
use App\Facades\Authentication;
use App\Models\Department;
use App\Models\User;
use App\Services\Authentication\AuthenticationException;
use Illuminate\Support\Facades\Session;
use Ramsey\Uuid\UuidInterface;

trait UserHelper
{
    /**
     * @param string|null $guard
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint
     */
    public function assertGuest($guard = null): void
    {
        $this->assertThrows(static function (): void {
            Authentication::user();
        }, AuthenticationException::class);
    }

    protected function beUser(User $user, bool $setValidOtpSession = true, ?Department $department = null): self
    {
        $this->be($user);

        if ($setValidOtpSession === true) {
            Session::put('otp_authenticated', $user->id);
        }

        if ($department !== null) {
            $user->update(['last_visited_department_id' => $department->id]);

            $user->departments()->attach($department, ['role' => DepartmentRole::WRITE]);
        }

        return $this;
    }

    protected function getUserById(UuidInterface $id): User
    {
        return User::query()
            ->findOrFail($id);
    }
}
