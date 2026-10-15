<?php

declare(strict_types=1);

namespace App\Http\Requests\Tag;

use App\Models\Tag;
use App\Rules\UniqueTagNameRule;
use Illuminate\Routing\Route;
use Override;
use Webmozart\Assert\Assert;

class TagUpdateRequest extends TagCreateRequest
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function rules(): array
    {
        $rules = parent::rules();

        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $tag = $route->parameter('tag');
        Assert::isInstanceOf($tag, Tag::class);

        $rules['name'] = [
            'required',
            'string',
            'max:64',
            new UniqueTagNameRule($this->department()->id, $tag->id),
        ];

        return $rules;
    }
}
