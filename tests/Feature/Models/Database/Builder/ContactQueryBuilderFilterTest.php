<?php

declare(strict_types=1);

namespace Tests\Feature\Models\Database\Builder;

use App\Enums\ContactCriteria;
use App\Models\Builder\Contact\ContactQueryBuilder;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

class ContactQueryBuilderFilterTest extends FeatureTestCase
{
    public function testEmpty(): void
    {
        $contactQueryBuilder = ContactQueryBuilder::make();
        $this->assertEquals(0, $contactQueryBuilder->count());
    }

    public function testWithoutFilters(): void
    {
        $count = $this->faker->numberBetween(3, 5);
        Contact::factory()
            ->count($count)
            ->create();

        $this->assertEquals($count, ContactQueryBuilder::make()->count());
    }

    public function testSearchInTwoFields(): void
    {
        $seachTerm = $this->faker->unique()->word;
        $request = new Request([
            'filter' => [
                ContactCriteria::SEARCH->value => $seachTerm,
            ],
        ]);

        Contact::factory()->create([
            'last_name' => $seachTerm,
        ]);
        Contact::factory()->create([
            'organisation_name' => $seachTerm,
        ]);

        $this->assertEquals(2, ContactQueryBuilder::make($request)->count());
    }

    public function testSearchTwoTermsInTwoFields(): void
    {
        $seachTermA = $this->faker->unique()->word;
        $seachTermB = $this->faker->unique()->word;
        $request = new Request([
            'filter' => [
                ContactCriteria::SEARCH->value => $seachTermA . ' ' . $seachTermB,
            ],
        ]);

        Contact::factory()->create([
            'last_name' => $seachTermA . ' ' . $seachTermB,
        ]);
        Contact::factory()->create([
            'last_name' => $seachTermA,
            'organisation_name' => $seachTermB,
        ]);

        $this->assertEquals(2, ContactQueryBuilder::make($request)->count());
    }

    #[Test]
    public function testNotArchivedIncludesOnlyNonArchivedContacts(): void
    {
        Contact::factory()->count(3)->create([
            'archived_at' => null,
        ]);
        Contact::factory()->count(2)->create([
            'archived_at' => Carbon::now(),
        ]);

        $this->assertEquals(3, Contact::query()->notArchived()->count());
    }
}
