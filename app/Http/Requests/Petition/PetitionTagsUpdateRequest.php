<?php

declare(strict_types=1);

namespace App\Http\Requests\Petition;

use App\Http\Requests\FormRequest;
use App\Models\Petition;
use App\Models\Tag;
use Illuminate\Routing\Route;
use Illuminate\Validation\Rule;
use Override;
use Webmozart\Assert\Assert;

use function array_filter;
use function is_array;

class PetitionTagsUpdateRequest extends FormRequest
{
    /**
     * Only the tags the edit form offers are accepted, see TagQueryBuilder::selectableForPetition().
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $route = $this->route();
        Assert::isInstanceOf($route, Route::class);

        $petition = $route->parameter('petition');
        Assert::isInstanceOf($petition, Petition::class);

        $selectableTagIds = Tag::query()
            ->selectableForPetition($petition)
            ->get()
            ->map(static fn(Tag $tag): string => $tag->id->toString())
            ->all();

        return [
            'tag_ids' => [
                'array',
            ],
            'tag_ids.*' => [
                'bail',
                'string',
                Rule::in($selectableTagIds),
            ],
        ];
    }

    #[Override]
    protected function prepareForValidation(): void
    {
        $tagIds = $this->input('tag_ids', []);

        if (!is_array($tagIds)) {
            return;
        }

        $this->merge([
            'tag_ids' => array_filter($tagIds),
        ]);
    }
}
