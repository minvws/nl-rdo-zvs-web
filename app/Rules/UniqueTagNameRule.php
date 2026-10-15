<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Tag;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Ramsey\Uuid\UuidInterface;

use function is_string;

/**
 * Tag names are unique per department, ignoring case. The database enforces the same through a unique index on
 * (department_id, lower(name)); this rule turns a clash into a form error instead of a query exception.
 */
class UniqueTagNameRule implements ValidationRule
{
    public function __construct(
        private readonly UuidInterface $departmentId,
        private readonly ?UuidInterface $ignoreTagId = null,
    ) {
    }

    /**
     * @param Closure(string, ?string=): PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('validation.string')->translate();

            return;
        }

        $query = Tag::query()
            ->where('department_id', $this->departmentId->toString())
            ->whereRaw('lower(name) = lower(?)', [$value]);

        if ($this->ignoreTagId instanceof UuidInterface) {
            $query->whereKeyNot($this->ignoreTagId->toString());
        }

        if ($query->exists()) {
            $fail('validation.unique_tag_name')->translate();
        }
    }
}
