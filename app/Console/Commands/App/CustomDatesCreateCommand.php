<?php

declare(strict_types=1);

namespace App\Console\Commands\App;

use App\Enums\CustomDateLabel;
use App\Models\Department;
use App\Models\PetitionType;
use App\Models\PetitionTypeCustomDateLabel;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\DatabaseManager;
use Laravel\Prompts\Concerns\Colors;
use Throwable;
use Webmozart\Assert\Assert;

use function __;
use function collect;
use function in_array;
use function Laravel\Prompts\select;
use function sprintf;

#[Description('Create a new Custom Date Label for a Petition Type')]
#[Signature('app:custom-dates:create')]
class CustomDatesCreateCommand extends Command
{
    use Colors;

    public function __construct(
        private readonly DatabaseManager $databaseManager,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Custom Date Creation for Petition Types');
        $this->info('=========================================');

        $department = $this->selectDepartment();
        $petitionType = $this->selectPetitionType($department);
        $customDateLabel = $this->selectCustomDateLabel($petitionType);

        if (!$customDateLabel instanceof CustomDateLabel) {
            return self::FAILURE;
        }

        $displayName = __('custom_dates.' . $customDateLabel->value);

        $confirmed = $this->confirm(
            sprintf(
                'Are you sure you want to add "%s" to petition type "%s" in team "%s"?',
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
            $this->createCustomDateLabel($petitionType, $customDateLabel);

            $this->info(
                sprintf(
                    'Custom Date Label "%s" successfully added to Petition Type "%s" in team "%s".',
                    $displayName,
                    $petitionType->name,
                    $department->name,
                ),
            );

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Error during creation: ' . $e->getMessage());

            return self::FAILURE;
        }
    }

    private function selectDepartment(): Department
    {
        $departments = Department::query()
            ->orderBy('name')
            ->get();

        /** @var array<string, string> $choices */
        $choices = $departments->mapWithKeys(static function (Department $department): array {
            return [$department->id->toString() => $department->name];
        })->all();

        $selectedDepartment = select('Select a Team (Department)', $choices);

        $department = $departments->firstWhere('id', $selectedDepartment);
        Assert::isInstanceOf($department, Department::class);

        return $department;
    }

    private function selectPetitionType(Department $department): PetitionType
    {
        $petitionTypes = PetitionType::query()
            ->where('department_id', $department->id)
            ->orderBy('name')
            ->get();

        /** @var array<string, string> $choices */
        $choices = $petitionTypes->mapWithKeys(static function (PetitionType $type): array {
            return [$type->id->toString() => sprintf('%s (%s)', $type->name, $type->type->value)];
        })->all();

        $selectedPetitionType = select('Select a Petition Type', $choices);

        $petitionType = $petitionTypes->firstWhere('id', $selectedPetitionType);
        Assert::isInstanceOf($petitionType, PetitionType::class);

        return $petitionType;
    }

    private function selectCustomDateLabel(PetitionType $petitionType): ?CustomDateLabel
    {
        $allLabels = CustomDateLabel::cases();
        $inUseLabels = $petitionType->customDateLabels->pluck('date_label')->all();

        $availableLabels = collect($allLabels)->filter(static function (CustomDateLabel $label) use ($inUseLabels): bool {
            return !in_array($label, $inUseLabels, true);
        });

        if ($availableLabels->isEmpty()) {
            $this->error('Er zijn geen beschikbare datumlabels meer voor deze zaaksoort.');

            return null;
        }

        /** @var array<string, string> $choices */
        $choices = $availableLabels->mapWithKeys(static function (CustomDateLabel $label): array {
            $displayName = __('custom_dates.' . $label->value);
            Assert::string($displayName);

            return [$displayName => $displayName];
        })->all();

        $selectedDisplayName = select('Select a Custom Date Label to add', $choices);

        $selectedLabel = $availableLabels
            ->filter(static fn(CustomDateLabel $label): bool => __('custom_dates.' . $label->value) === $selectedDisplayName)
            ->first();

        Assert::isInstanceOf($selectedLabel, CustomDateLabel::class);

        return $selectedLabel;
    }

    private function createCustomDateLabel(PetitionType $petitionType, CustomDateLabel $customDateLabel): void
    {
        $this->databaseManager->transaction(static function () use ($petitionType, $customDateLabel): void {
            PetitionTypeCustomDateLabel::query()->create([
                'petition_type_id' => $petitionType->id,
                'date_label' => $customDateLabel,
            ]);
        });
    }
}
