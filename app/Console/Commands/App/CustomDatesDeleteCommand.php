<?php

declare(strict_types=1);

namespace App\Console\Commands\App;

use App\Actions\CustomDates\CustomDatesDeleteAction;
use App\Enums\CustomDateLabel;
use App\Models\Department;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;
use Webmozart\Assert\Assert;

use function __;
use function Laravel\Prompts\select;
use function sprintf;

#[Description('Delete a Custom Date Label from a Petition Type')]
#[Signature('app:custom-dates:delete')]
class CustomDatesDeleteCommand extends Command
{
    public function __construct(
        private readonly CustomDatesDeleteAction $action,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Custom Date Deletion for Petition Types');
        $this->info('=========================================');

        $department = $this->selectDepartment();
        if (!$department instanceof Department) {
            return self::FAILURE;
        }

        $petitionType = $this->selectPetitionType($department);
        if (!$petitionType instanceof PetitionType) {
            return self::FAILURE;
        }

        $customDateLabel = $this->selectCustomDateLabel($petitionType);

        $displayName = __('custom_dates.' . $customDateLabel->value);

        $confirmed = $this->confirm(
            sprintf(
                'Are you sure you want to delete "%s" from petition type "%s" in team "%s"? This will also delete all associated custom dates on petitions.',
                $displayName,
                $petitionType->name,
                $department->name,
            ),
        );

        if (!$confirmed) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            $this->action->execute($petitionType, $customDateLabel);

            $this->info(
                sprintf(
                    'Custom Date Label "%s" successfully deleted from Petition Type "%s" in team "%s".',
                    $displayName,
                    $petitionType->name,
                    $department->name,
                ),
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error during deletion: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function selectDepartment(): ?Department
    {
        $departments = Department::query()
            ->orderBy('name')
            ->get();

        if ($departments->isEmpty()) {
            $this->error('No departments found.');

            return null;
        }

        /** @var array<string, string> $choices */
        $choices = $departments->mapWithKeys(static function (Department $department): array {
            return [(string) $department->id => $department->name];
        })->all();

        $selectedId = select(label: 'Select a Team (Department)', options: $choices);

        return $departments->find($selectedId);
    }

    private function selectPetitionType(Department $department): ?PetitionType
    {
        $petitionTypes = PetitionType::query()
            ->where('department_id', $department->id)
            ->with('customDateLabels')
            ->whereHas('customDateLabels')
            ->orderBy('name')
            ->get();

        if ($petitionTypes->isEmpty()) {
            $this->error(sprintf('No petition types with custom dates found for team "%s".', $department->name));

            return null;
        }

        /** @var array<string, string> $choices */
        $choices = $petitionTypes->mapWithKeys(static function (PetitionType $type): array {
            return [(string) $type->id => sprintf('%s (%s)', $type->name, $type->type->value)];
        })->all();

        $selectedId = select(label: 'Select a Petition Type', options: $choices);

        return $petitionTypes->find($selectedId);
    }

    private function selectCustomDateLabel(PetitionType $petitionType): CustomDateLabel
    {
        $customDateLabels = $petitionType->customDateLabels;

        /** @var array<string, string> $choices */
        $choices = $customDateLabels->mapWithKeys(static function (PetitionTypeCustomDateLabel $label): array {
            $displayName = __('custom_dates.' . $label->date_label->value);
            Assert::string($displayName);

            return [$displayName => $displayName];
        })->toArray();

        $selectedDisplayName = select(label: 'Select a Custom Date Label to delete', options: $choices);

        $selectedLabel = $customDateLabels
            ->filter(
                static fn(PetitionTypeCustomDateLabel $label): bool => __(
                    'custom_dates.' . $label->date_label->value,
                ) === $selectedDisplayName,
            )
            ->first();

        Assert::isInstanceOf($selectedLabel, PetitionTypeCustomDateLabel::class);

        return $selectedLabel->date_label;
    }
}
