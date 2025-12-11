<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Actions\CustomDates\CustomDatesAdjustAction;
use App\Enums\CustomDateLabel;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use Exception;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function expect;
use function now;

class CustomDatesAdjustCommandTest extends FeatureTestCase
{
    #[Test]
    public function successfullyAdjustsCustomDateLabels(): void
    {
        $date = now()->addWeek();
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);
        $petition = Petition::factory()->recycle($petitionType)->create();
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $date->format('Y-m-d'),
        ]);

        $this->artisan('app:custom-dates:adjust')
            ->expectsQuestion('Select a Department', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select the current label to change', CustomDateLabel::DATE_PUBLIC_HEARING->value)
            ->expectsQuestion('Select the new label', CustomDateLabel::DATE_COURT_SESSION->value)
            ->expectsConfirmation(
                'Are you sure you want to change all "Hoorzitting" labels to "Zitting" for petition type "Test Petition Type"?',
                'yes',
            )
            ->assertSuccessful();

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
        ]);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseHas('petition_custom_dates', [
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
            'date' => $date,
        ]);
    }

    #[Test]
    public function cancelsWhenUserDoesNotConfirm(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        $customDateLabel = PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $this->artisan('app:custom-dates:adjust')
            ->expectsQuestion('Select a Department', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select the current label to change', CustomDateLabel::DATE_PUBLIC_HEARING->value)
            ->expectsQuestion('Select the new label', CustomDateLabel::DATE_COURT_SESSION->value)
            ->expectsConfirmation(
                'Are you sure you want to change all "Hoorzitting" labels to "Zitting" for petition type "Test Petition Type"?',
                'no',
            )
            ->assertSuccessful();

        $customDateLabel->refresh();
        expect($customDateLabel->date_label)->toBe(CustomDateLabel::DATE_PUBLIC_HEARING);
    }

    #[Test]
    public function failsWhenNoPetitionTypesWithCustomDatesFound(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        PetitionType::factory()->recycle($department)->count(3)->create();

        $this->artisan('app:custom-dates:adjust')
            ->expectsQuestion('Select a Department', $department->id->toString())
            ->expectsOutputToContain('No petition types with custom dates found for department "Test Department".')
            ->assertFailed();
    }

    #[Test]
    public function handlesExceptionDuringAdjustment(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $mockAction = $this->createMock(CustomDatesAdjustAction::class);
        $mockAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception('Database error'));

        $this->app->instance(CustomDatesAdjustAction::class, $mockAction);

        $this->artisan('app:custom-dates:adjust')
            ->expectsQuestion('Select a Department', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select the current label to change', CustomDateLabel::DATE_PUBLIC_HEARING->value)
            ->expectsQuestion('Select the new label', CustomDateLabel::DATE_COURT_SESSION->value)
            ->expectsConfirmation(
                'Are you sure you want to change all "Hoorzitting" labels to "Zitting" for petition type "Test Petition Type"?',
                'yes',
            )
            ->expectsOutputToContain('Error during adjustment: Database error')
            ->assertFailed();
    }

    #[Test]
    public function failsWhenNoDepartmentsFound(): void
    {
        $this->artisan('app:custom-dates:adjust')
            ->expectsOutputToContain('No departments found.')
            ->assertFailed();
    }
}
