<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Collections\PetitionCollection;
use App\Enums\PetitionCriteria;
use App\Models\Builder\Petition\PetitionQueryBuilder;
use App\Models\Petition;
use App\ValueObjects\CalendarDate;
use Illuminate\Http\Request;
use Tests\Feature\FeatureTestCase;
use Webmozart\Assert\Assert;

class PetitionDeadlineFilterTest extends FeatureTestCase
{
    public function testFilterFromKeepsPetitionsOnOrAfterTheDate(): void
    {
        $early = $this->createPetitionWithDeadline('01-01-2026');
        $late = $this->createPetitionWithDeadline('01-06-2026');

        $result = $this->filter(PetitionCriteria::DEADLINE_FROM, '2026-03-01');

        $this->assertFalse($result->contains('id', $early->id));
        $this->assertTrue($result->contains('id', $late->id));
    }

    public function testFilterToKeepsPetitionsOnOrBeforeTheDate(): void
    {
        $early = $this->createPetitionWithDeadline('01-01-2026');
        $late = $this->createPetitionWithDeadline('01-06-2026');

        $result = $this->filter(PetitionCriteria::DEADLINE_TO, '2026-03-01');

        $this->assertTrue($result->contains('id', $early->id));
        $this->assertFalse($result->contains('id', $late->id));
    }

    public function testFilterUsesTheOverrideWhenItIsSet(): void
    {
        $petition = Petition::factory()->create([
            'deadline_at' => CalendarDate::create('01-01-2026'),
            'deadline_override_at' => CalendarDate::create('01-06-2026'),
            'deadline_override_reason' => 'Afspraak met verzoeker',
        ]);

        $this->assertTrue($this->filter(PetitionCriteria::DEADLINE_FROM, '2026-03-01')->contains('id', $petition->id));
        $this->assertFalse($this->filter(PetitionCriteria::DEADLINE_TO, '2026-03-01')->contains('id', $petition->id));
    }

    public function testFilterIsIgnoredForAValueThatIsNotADate(): void
    {
        $petition = $this->createPetitionWithDeadline('01-01-2026');

        $this->assertTrue($this->filter(PetitionCriteria::DEADLINE_TO, 'gisteren')->contains('id', $petition->id));
        $this->assertTrue($this->filter(PetitionCriteria::DEADLINE_TO, '')->contains('id', $petition->id));
    }

    public function testFilterIsIgnoredForAValueThatIsNotAString(): void
    {
        $petition = $this->createPetitionWithDeadline('01-01-2026');

        $result = $this->filter(PetitionCriteria::DEADLINE_TO, ['2026-03-01']);

        $this->assertTrue($result->contains('id', $petition->id));
    }

    private function createPetitionWithDeadline(string $deadline): Petition
    {
        return Petition::factory()->create([
            'deadline_at' => CalendarDate::create($deadline),
            'deadline_override_at' => null,
            'deadline_override_reason' => null,
        ]);
    }

    private function filter(PetitionCriteria $criteria, mixed $value): PetitionCollection
    {
        $request = new Request(['filter' => [$criteria->value => $value]]);

        $petitions = PetitionQueryBuilder::make($request)->get();
        Assert::isInstanceOf($petitions, PetitionCollection::class);

        return $petitions;
    }
}
