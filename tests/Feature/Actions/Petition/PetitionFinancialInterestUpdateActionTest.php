<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\Petition;

use App\Actions\Petition\PetitionFinancialInterestUpdateAction;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\User;
use Tests\Feature\FeatureTestCase;

class PetitionFinancialInterestUpdateActionTest extends FeatureTestCase
{
    public function testStoresTheAmountAndWritesTimelineItem(): void
    {
        $petition = Petition::factory()->recycle(Department::factory()->create())->create(['amount_in_dispute' => null]);
        $user = User::factory()->create();

        $this->app->make(PetitionFinancialInterestUpdateAction::class)
            ->execute($petition, $user, ['amount_in_dispute' => 300_000]);

        $this->assertSame(300_000, $petition->refresh()->amount_in_dispute);

        $timelineItem = $petition->timelineItems()->where('type', TimelineType::FINANCIAL_INTEREST_UPDATED)->sole();
        $this->assertSame(300_000, $timelineItem->data['amount_in_dispute']);
        $this->assertEquals($user->id, $timelineItem->user_id);
    }

    public function testStoresTheAmountAsIntegerWhenGivenAsString(): void
    {
        $petition = Petition::factory()->recycle(Department::factory()->create())->create(['amount_in_dispute' => null]);

        $this->app->make(PetitionFinancialInterestUpdateAction::class)
            ->execute($petition, User::factory()->create(), ['amount_in_dispute' => '300000']);

        $timelineItem = $petition->timelineItems()->where('type', TimelineType::FINANCIAL_INTEREST_UPDATED)->sole();
        $this->assertSame(300_000, $timelineItem->data['amount_in_dispute']);
    }

    public function testClearingTheAmountWritesTimelineItem(): void
    {
        $petition = Petition::factory()->recycle(Department::factory()->create())->create(['amount_in_dispute' => 500]);

        $this->app->make(PetitionFinancialInterestUpdateAction::class)
            ->execute($petition, User::factory()->create(), ['amount_in_dispute' => null]);

        $this->assertNull($petition->refresh()->amount_in_dispute);

        $timelineItem = $petition->timelineItems()->where('type', TimelineType::FINANCIAL_INTEREST_UPDATED)->sole();
        $this->assertNull($timelineItem->data['amount_in_dispute']);
    }

    public function testSavingWithoutChangeWritesNoTimelineItem(): void
    {
        $petition = Petition::factory()->recycle(Department::factory()->create())->create(['amount_in_dispute' => 500]);

        $this->app->make(PetitionFinancialInterestUpdateAction::class)
            ->execute($petition, User::factory()->create(), ['amount_in_dispute' => 500]);

        $this->assertFalse($petition->timelineItems()->where('type', TimelineType::FINANCIAL_INTEREST_UPDATED)->exists());
    }
}
