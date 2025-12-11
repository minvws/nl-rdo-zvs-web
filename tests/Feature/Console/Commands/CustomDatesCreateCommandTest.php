<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use App\Enums\CustomDateLabel;
use App\Models\Department;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\FeatureTestCase;

use function __;
use function sprintf;

class CustomDatesCreateCommandTest extends FeatureTestCase
{
    #[Test]
    public function successfullyCreatesCustomDateLabel(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->expectsOutputToContain(
                sprintf(
                    'Custom Date Label "%s" successfully added to Petition Type "%s" in team "%s".',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function cancelsWhenUserDoesNotConfirm(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'no',
            )
            ->expectsOutputToContain('Operation cancelled.')
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function handlesExceptionDuringCreation(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        $databaseManagerMock = $this->mock(DatabaseManager::class);

        $this->app->instance(DatabaseManager::class, $databaseManagerMock);

        $databaseManagerMock->expects('transaction')
            ->once()
            ->andThrow(new Exception('Simulated exception'));

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_COURT_SESSION->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_COURT_SESSION->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::FAILURE);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
        ]);
    }

    #[Test]
    public function onlyShowsAvailableCustomDateLabels(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_PUBLIC_HEARING]);
        PetitionTypeCustomDateLabel::factory()
            ->recycle($petitionType)
            ->create(['date_label' => CustomDateLabel::DATE_RULING]);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_COURT_SESSION->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_COURT_SESSION->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
        ]);
    }

    #[Test]
    public function filtersPetitionTypesByDepartment(): void
    {
        $department1 = Department::factory()->create(['name' => 'Department 1']);
        $department2 = Department::factory()->create(['name' => 'Department 2']);
        $petitionType1 = PetitionType::factory()->recycle($department1)->create(['name' => 'Petition Type 1']);
        $petitionType2 = PetitionType::factory()->recycle($department2)->create(['name' => 'Petition Type 2']);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department1->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType1->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType1->name,
                    $department1->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType1->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);

        $this->assertDatabaseMissing('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType2->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
    }

    #[Test]
    public function createsMultipleCustomDateLabelsForSamePetitionType(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_PUBLIC_HEARING->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsQuestion('Select a Custom Date Label to add', __('custom_dates.' . CustomDateLabel::DATE_COURT_SESSION->value))
            ->expectsConfirmation(
                sprintf(
                    'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
                    __('custom_dates.' . CustomDateLabel::DATE_COURT_SESSION->value),
                    $petitionType->name,
                    $department->name,
                ),
                'yes',
            )
            ->assertExitCode(Command::SUCCESS);

        $this->assertDatabaseCount('petition_type_custom_dates_labels', 2);
        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_PUBLIC_HEARING,
        ]);
        $this->assertDatabaseHas('petition_type_custom_dates_labels', [
            'petition_type_id' => $petitionType->id,
            'date_label' => CustomDateLabel::DATE_COURT_SESSION,
        ]);
    }

    #[Test]
    public function failsWhenNoAvailableCustomDateLabelsExist(): void
    {
        $department = Department::factory()->create(['name' => 'Test Department']);
        $petitionType = PetitionType::factory()->recycle($department)->create(['name' => 'Test Petition Type']);

        // Create all possible custom date labels for this petition type
        foreach (CustomDateLabel::cases() as $label) {
            PetitionTypeCustomDateLabel::factory()
                ->recycle($petitionType)
                ->create(['date_label' => $label]);
        }

        $this->artisan('app:custom-dates:create')
            ->expectsQuestion('Select a Team (Department)', $department->id->toString())
            ->expectsQuestion('Select a Petition Type', $petitionType->id->toString())
            ->expectsOutputToContain('Er zijn geen beschikbare datumlabels meer voor deze zaaksoort.')
            ->assertExitCode(Command::FAILURE);
    }
}
