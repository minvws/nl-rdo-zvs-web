<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use App\Models\Department;
use App\Models\Tag;
use App\Rules\UniqueTagNameRule;
use Illuminate\Translation\PotentiallyTranslatedString;
use Tests\Feature\FeatureTestCase;

use function app;

class UniqueTagNameRuleTest extends FeatureTestCase
{
    public function testFailsOnNonString(): void
    {
        $department = Department::factory()->create();

        $this->assertSame(['validation.string'], $this->validate(new UniqueTagNameRule($department->id), 42));
    }

    public function testPassesForUnusedName(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);

        $this->assertSame([], $this->validate(new UniqueTagNameRule($department->id), 'Mediation'));
    }

    public function testFailsForNameAlreadyUsedInDepartmentRegardlessOfCase(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);

        $this->assertSame(['validation.unique_tag_name'], $this->validate(new UniqueTagNameRule($department->id), 'VEELKLAGER'));
    }

    public function testComparesCaseTheSameWayAsTheDatabaseIndex(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Één keer']);

        $this->assertSame(['validation.unique_tag_name'], $this->validate(new UniqueTagNameRule($department->id), 'één KEER'));
    }

    public function testPassesForNameUsedInOtherDepartment(): void
    {
        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        Tag::factory()->recycle($otherDepartment)->create(['name' => 'Veelklager']);

        $this->assertSame([], $this->validate(new UniqueTagNameRule($department->id), 'Veelklager'));
    }

    public function testIgnoresTheTagBeingEdited(): void
    {
        $department = Department::factory()->create();
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);

        $this->assertSame([], $this->validate(new UniqueTagNameRule($department->id, $tag->id), 'veelklager'));
    }

    public function testIgnoringOneTagStillFailsOnAnotherTagWithTheSameName(): void
    {
        $department = Department::factory()->create();
        Tag::factory()->recycle($department)->create(['name' => 'Veelklager']);
        $tag = Tag::factory()->recycle($department)->create(['name' => 'Mediation']);

        $this->assertSame(['validation.unique_tag_name'], $this->validate(new UniqueTagNameRule($department->id, $tag->id), 'Veelklager'));
    }

    /**
     * @return list<string>
     */
    private function validate(UniqueTagNameRule $rule, mixed $value): array
    {
        $failures = [];

        $rule->validate('name', $value, function (string $message) use (&$failures): PotentiallyTranslatedString {
            $failures[] = $message;

            return new PotentiallyTranslatedString($message, app('translator'));
        });

        return $failures;
    }
}
