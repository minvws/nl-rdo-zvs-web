<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProcessingStepStatus;
use App\Rules\CalendarDateRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessingStepCreateRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'deadline_at' => ['nullable', new CalendarDateRule()],
            'status' => ['required', Rule::enum(ProcessingStepStatus::class)],
            'assigned_to' => ['nullable', 'uuid', 'exists:users,id'],
        ];
    }
}
