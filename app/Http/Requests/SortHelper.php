<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\SortDirection;
use BackedEnum;
use Illuminate\Http\Request;
use TypeError;
use ValueError;
use Webmozart\Assert\Assert;

use function is_string;
use function str_contains;
use function str_starts_with;

readonly class SortHelper
{
    public function __construct(
        private Request $request,
    ) {
    }

    /**
     * @throws TypeError
     * @throws ValueError
     */
    public function getAria(BackedEnum $parameter): string
    {
        Assert::string($parameter->value);

        if (!$this->request->has('sort')) {
            return SortDirection::NONE->value;
        }

        $sort = $this->request->query('sort');
        if (!is_string($sort)) {
            return SortDirection::NONE->value;
        }

        if (!str_contains($sort, $parameter->value)) {
            return SortDirection::NONE->value;
        }

        return str_starts_with($sort, '-')
            ? SortDirection::DESC->value
            : SortDirection::ASC->value;
    }

    /**
     * @throws TypeError
     * @throws ValueError
     */
    public function getLink(BackedEnum $parameter): string
    {
        Assert::string($parameter->value);

        if (!$this->request->has('sort')) {
            return $this->request->fullUrlWithQuery(['sort' => $parameter->value]);
        }

        $sort = $this->request->query('sort');
        if (!is_string($sort)) {
            return $this->request->fullUrlWithQuery(['sort' => $parameter->value]);
        }

        if (!str_contains($sort, $parameter->value)) {
            return $this->request->fullUrlWithQuery(['sort' => $parameter->value]);
        }

        if (str_starts_with($sort, '-')) {
            return $this->request->fullUrlWithoutQuery('sort');
        }

        return $this->request->fullUrlWithQuery(['sort' => '-' . $parameter->value]);
    }
}
