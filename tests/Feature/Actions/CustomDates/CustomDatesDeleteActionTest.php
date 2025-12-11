<?php

declare(strict_types=1);

namespace Tests\Feature\Actions\CustomDates;

use App\Actions\CustomDates\CustomDatesDeleteAction;
use App\Enums\CustomDateLabel;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

#[CoversClass(CustomDatesDeleteAction::class)]
class CustomDatesDeleteActionTest extends FeatureTestCase
{
    #[Test]
    public function deletesCustomDateLabelsSuccessfully(): void
    {
        $date = $this->faker->calendarDate();
        $petitionType = PetitionType::factory()->create();

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $petition = Petition::factory()->recycle($petitionType)->create();
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date,
        ]);

        $action = $this->app->make(CustomDatesDeleteAction::class);
        $action->execute($petitionType, CustomDateLabel::DATE_PUBLIC_HEARING);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function onlyDeletesCustomDatesForSpecificPetitionType(): void
    {
        $date = $this->faker->calendarDate();

        $petitionType1 = PetitionType::factory()->create();
        $petitionType2 = PetitionType::factory()->create();

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType1)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType2)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $petition1 = Petition::factory()->recycle($petitionType1)->create();
        $petition1->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date,
        ]);

        $petition2 = Petition::factory()->recycle($petitionType2)->create();
        $petition2->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date,
        ]);

        $action = $this->app->make(CustomDatesDeleteAction::class);
        $action->execute($petitionType1, CustomDateLabel::DATE_PUBLIC_HEARING);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType1->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            'petition_id' => $petition1->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType2->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseHas('petition_custom_dates', [
            'petition_id' => $petition2->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function deletesOnlySpecifiedCustomDateLabel(): void
    {
        $date1 = $this->faker->calendarDate();
        $date2 = $this->faker->calendarDate();

        $petitionType = PetitionType::factory()->create();

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_COURT_SESSION]);

        $petition = Petition::factory()->recycle($petitionType)->create();
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date1,
        ]);
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
            'date' => $date2,
        ]);

        $action = $this->app->make(CustomDatesDeleteAction::class);
        $action->execute($petitionType, CustomDateLabel::DATE_PUBLIC_HEARING);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
        ]);

        $this->assertDatabaseHas('petition_custom_dates', [
            'petition_id' => $petition->id,
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
        ]);
    }

    #[Test]
    public function handlesMultipleCustomDatesForSamePetitionType(): void
    {
        $date1 = $this->faker->calendarDate();
        $date2 = $this->faker->calendarDate();

        $petitionType = PetitionType::factory()->create();

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $petition1 = Petition::factory()->recycle($petitionType)->create();
        $petition1->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date1,
        ]);

        $petition2 = Petition::factory()->recycle($petitionType)->create();
        $petition2->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date2,
        ]);

        $action = $this->app->make(CustomDatesDeleteAction::class);
        $action->execute($petitionType, CustomDateLabel::DATE_PUBLIC_HEARING);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            'petition_id' => $petition1->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            'petition_id' => $petition2->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }
}
