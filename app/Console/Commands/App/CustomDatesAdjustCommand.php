<?php

declare(strict_types=1);

namespace App\Console\Commands\App;

use App\Actions\CustomDates\CustomDatesAdjustAction;
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
use function collect;
use function Laravel\Prompts\select;
use function sprintf;

#[Description('Adjust a Custom Date on a Petition Type')]
#[Signature('app:custom-dates:adjust')]
class CustomDatesAdjustCommand extends Command
{
    public function __construct(
        private readonly CustomDatesAdjustAction $action,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Custom Date Adjustment for Petition Types');
        $this->info('============================================');

        $department = $this->selectDepartment();
        if (!$department instanceof Department) {
            return self::FAILURE;
        }

        $petitionType = $this->selectPetitionType($department);
        if (!$petitionType instanceof PetitionType) {
            return self::FAILURE;
        }

        $currentCustomDateLabel = $this->selectCurrentCustomDateLabel($petitionType);
        $newCustomDateLabel = $this->selectNewCustomDateLabel($currentCustomDateLabel);

        $currentDisplayName = __('custom_dates.' . $currentCustomDateLabel->value);
        $newDisplayName = __('custom_dates.' . $newCustomDateLabel->value);

        $confirmed = $this->confirm(
            sprintf(
                'Are you sure you want to change all "%s" labels to "%s" for petition type "%s"?',
                $currentDisplayName,
                $newDisplayName,
                $petitionType->name,
            ),
        );

        if (!$confirmed) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        try {
            $this->action->execute($petitionType, $currentCustomDateLabel, $newCustomDateLabel);

            $currentDisplayName = __('custom_dates.' . $currentCustomDateLabel->value);
            $newDisplayName = __('custom_dates.' . $newCustomDateLabel->value);

            $this->info(
                sprintf(
                    'Successfully adjusted Custom Date from "%s" to "%s" for Petition Type "%s"',
                    $currentDisplayName,
                    $newDisplayName,
                    $petitionType->name,
                ),
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error during adjustment: ' . $e->getMessage());

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

        $selectedId = select(label: 'Select a Department', options: $choices);

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
            $this->error(sprintf('No petition types with custom dates found for department "%s".', $department->name));

            return null;
        }

        /** @var array<string, string> $choices */
        $choices = $petitionTypes->mapWithKeys(static function (PetitionType $type): array {
            return [(string) $type->id => sprintf('%s (%s)', $type->name, $type->type->value)];
        })->all();

        $selectedId = select(label: 'Select a Petition Type', options: $choices);

        return $petitionTypes->find($selectedId);
    }

    private function selectCurrentCustomDateLabel(PetitionType $petitionType): CustomDateLabel
    {
        $customDateLabels = $petitionType->customDateLabels;

        /** @var array<string, string> $choices */
        $choices = $customDateLabels->mapWithKeys(static function (PetitionTypeCustomDateLabel $label): array {
            $displayName = __('custom_dates.' . $label->date_label->value);
            Assert::string($displayName);

            return [$label->date_label->value => $displayName];
        })->toArray();

        $selectedValue = select(label: 'Select the current label to change', options: $choices);

        $selectedLabel = $customDateLabels
            ->filter(static fn(PetitionTypeCustomDateLabel $label): bool => $label->date_label->value === $selectedValue)
            ->first();

        Assert::isInstanceOf($selectedLabel, PetitionTypeCustomDateLabel::class);

        return $selectedLabel->date_label;
    }

    private function selectNewCustomDateLabel(CustomDateLabel $currentLabel): CustomDateLabel
    {
        $allLabels = CustomDateLabel::cases();

        $availableLabels = collect($allLabels)->filter(static function (CustomDateLabel $label) use ($currentLabel): bool {
            return $label !== $currentLabel;
        });

        /** @var array<string, string> $choices */
        $choices = $availableLabels->mapWithKeys(static function (CustomDateLabel $label): array {
            $displayName = __('custom_dates.' . $label->value);
            Assert::string($displayName);

            return [$label->value => $displayName];
        })->all();

        $selectedValue = select(label: 'Select the new label', options: $choices);

        $newLabel = $availableLabels
            ->filter(static fn(CustomDateLabel $label): bool => $label->value === $selectedValue)
            ->first();

        Assert::isInstanceOf($newLabel, CustomDateLabel::class);

        return $newLabel;
    }
}
