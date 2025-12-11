<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Enums\ExportType;
use App\Http\Requests\FormRequest;
use App\Rules\CalendarDateRule;
use Illuminate\Validation\Rule;

class PetitionExportRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'export_type' => [
                'required',
                Rule::enum(ExportType::class),
            ],
            'petition_type_id' => [
                'required',
                'uuid',
            ],
            'petition_category_id' => [
                'nullable',
                'uuid',
            ],
            'date_from' => [
                'required',
                new CalendarDateRule(),
                'before_or_equal:date_to',
            ],
            'date_to' => [
                'required',
                new CalendarDateRule(),
                'after_or_equal:date_from',
            ],
        ];
    }
}
