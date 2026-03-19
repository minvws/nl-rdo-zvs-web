<?php

declare(strict_types=1);

namespace Tests\Feature\Factories;

use App\Enums\PetitionEventType;
use App\Factories\WizardEventCollectionFactory;
use App\Models\PetitionEvent;
use App\ValueObjects\PetitionEventData;
use App\ValueObjects\WizardEventCollection;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function collect;

class WizardEventCollectionFactoryTest extends FeatureTestCase
{
    #[Test]
    public function createsWizardEventCollectionFromEloquentModels(): void
    {
        $e1 = new PetitionEvent();
        $e1->type = PetitionEventType::cases()[0];
        $e1->date = CarbonImmutable::now()->toDateString();
        $e1->created_at = CarbonImmutable::now()->subDays(2);
        $e1->duration = 5;
        $e1->penalties = [['amount' => 1, 'duration' => 7]];

        $e2 = new PetitionEvent();
        $e2->type = PetitionEventType::cases()[0];
        $e2->date = CarbonImmutable::now()->toDateString();
        $e2->created_at = CarbonImmutable::now()->subDay();
        $e2->duration = null;
        $e2->penalties = [];

        $collection = collect([$e1, $e2]);

        $result = WizardEventCollectionFactory::fromModels($collection);

        $this->assertInstanceOf(WizardEventCollection::class, $result);
        $this->assertSame(2, $result->count());
        $this->assertInstanceOf(PetitionEventData::class, $result->last());

        $array = $result->toArray();

        $this->assertSame([
            ['amount' => 1, 'duration' => 7],
        ], $array[0]['penalties']);
        $this->assertInstanceOf(CarbonImmutable::class, $array[0]['created_at']);
        $this->assertSame([], $array[1]['penalties'] ?? []);
    }

    #[Test]
    public function returnsEmptyWizardEventCollectionForEmptyInput(): void
    {
        $result = WizardEventCollectionFactory::fromModels(collect());

        $this->assertInstanceOf(WizardEventCollection::class, $result);
        $this->assertTrue($result->isEmpty());
    }
}
