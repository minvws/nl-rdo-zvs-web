<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Actions\CustomDates\CustomDatesDeleteAction;
use App\Enums\CustomDateLabel;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use Exception;
use Illuminate\Console\Command;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function sprintf;

class CustomDatesDeleteCommandTest extends FeatureTestCase
{
    #[Test]
    public function successfullyDeletesCustomDateLabel(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to delete', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->expectsOutputToContain(
                sprintf(
                    'Custom Date Label "%s" successfully deleted from Petition Type "%s" in team "%s".',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function cancelsWhenUserDoesNotConfirm(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to delete', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'no',
            )
            ->expectsOutputToContain('Operation cancelled.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function handlesExceptionDuringDeletion(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $mockAction = $this->createMock(CustomDatesDeleteAction::class);
        $mockAction->expects($this->once())
            ->method('execute')
            ->willThrowException(new Exception('Database error'));

        $this->app->instance(CustomDatesDeleteAction::class, $mockAction);

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to delete', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->expectsOutputToContain('Error during deletion: Database error')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function failsWhenNoDepartmentsFound(): void
    {
        $this->artisan('app:custom-dates:delete')
            ->expectsOutputToContain('No departments found.')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function failsWhenNoPetitionTypesWithCustomDatesFound(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        PetitionType::factory()->recycle($department)->count(3)->create();

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsOutputToContain('No petition types with custom dates found for team "Test Department".')
            ->assertExitCode(Command::FAILURE);
    }

    #[Test]
    public function deletesAssociatedPetitionCustomDates(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $petition = Petition::factory()->recycle($petitionType)->create();
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $this->faker->calendarDate(),
        ]);

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to delete', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            ['petition_type_id' => $petitionType->id, 'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING],
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            ['petition_id' => $petition->id, 'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING],
        ]);
    }

    #[Test]
    public function onlyDeletesCustomDatesForSpecificPetitionType(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType1 = PetitionType::factory()->recycle($department)->create(['name' => 'Petition Type 1']);
        $petitionType2 = PetitionType::factory()->recycle($department)->create(['name' => 'Petition Type 2']);

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType1)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType2)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $petition1 = Petition::factory()->recycle($petitionType1)->create();
        $petition1->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $this->faker->calendarDate(),
        ]);

        $petition2 = Petition::factory()->recycle($petitionType2)->create();
        $petition2->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $this->faker->calendarDate(),
        ]);

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType1->id->toString())
            ->expectsQuestion('Select a Custom Date Label to delete', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType1->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            ['petition_type_id' => $petitionType1->id, 'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING],
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            ['petition_id' => $petition1->id, 'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING],
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
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_COURT_SESSION]);

        $petition = Petition::factory()->recycle($petitionType)->create();
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
            'date' => $this->faker->calendarDate(),
        ]);
        $petition->customDates()->create([
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
            'date' => $this->faker->calendarDate(),
        ]);

        $this->artisan('app:custom-dates:delete')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to delete', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            ['petition_type_id' => $petitionType->id, 'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING],
        ]);

        $this->assertDatabaseMissing('petition_custom_dates', [
            ['petition_id' => $petition->id, 'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING],
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
}
