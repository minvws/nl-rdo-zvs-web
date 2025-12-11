<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Enums\Authorization\DepartmentRole;
use App\Enums\Authorization\GlobalRole;
use App\Http\Requests\FormRequest;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class UserCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:128',
            ],
            'email' => [
                'required',
                Rule::email(),
                'max:128',
                'ends_with:' . Config::string('auth.allowed_user_email_domains'),
                Rule::unique(User::class),
            ],
            'active' => [
                'required',
                'boolean',
            ],
            'global_roles' => [
                'array',
            ],
            'global_roles.*' => [
                Rule::enum(GlobalRole::class),
            ],
            'department_roles' => [
                'array',
            ],
            'department_roles.*' => [
                'required',
                'array',
            ],
            'department_roles.*.*' => [
                Rule::enum(DepartmentRole::class),
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->string('email')
                ->trim()
                ->lower()
                ->toString(),
        ]);
    }
}
