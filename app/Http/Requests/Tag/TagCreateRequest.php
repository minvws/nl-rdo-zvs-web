<?php

declare(strict_types=1);

namespace App\Http\Requests\Tag;

use App\Enums\TagColor;
use App\Http\Requests\FormRequest;
use App\Models\Department;
use App\Rules\UniqueTagNameRule;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Webmozart\Assert\Assert;

class TagCreateRequest extends FormRequest
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
                'max:64',
                new UniqueTagNameRule($this->department()->id),
            ],
            'color' => [
                'required',
                Rule::enum(TagColor::class),
            ],
            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
            'active' => [
                'required',
                'boolean',
            ],
        ];
    }

    protected function department(): Department
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $department = $route->parameter('department');
        Assert::isInstanceOf($department, Department::class);

        return $department;
    }
}
