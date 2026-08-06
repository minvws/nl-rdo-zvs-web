<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TermType;
use App\Models\Builder\DepartmentTermTypeSettingBuilder;
use App\Models\Concerns\HasId;
use Database\Factories\DepartmentTermTypeSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Override;
use Ramsey\Uuid\UuidInterface;

/**
 * @property UuidInterface $department_id
 * @property TermType $termType
 * @property string $field
 * @property bool $active
 * @property string $default_value
 * @property ?string $title
 *
 * @mixin DepartmentTermTypeSettingBuilder
 */
#[Table('department_term_type_settings', timestamps: false)]
#[UseEloquentBuilder(DepartmentTermTypeSettingBuilder::class)]
#[UseFactory(DepartmentTermTypeSettingFactory::class)]
class DepartmentTermTypeSetting extends EloquentModel
{
    /** @use HasFactory<DepartmentTermTypeSettingFactory> */
    use HasFactory;
    use HasId;

    /**
     * @return array<string, class-string<TermType>|string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'term_type' => TermType::class,
        ];
    }
}
