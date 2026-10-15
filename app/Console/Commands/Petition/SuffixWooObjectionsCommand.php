<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Enums\Occurrence;
use App\Enums\PetitionVariant;
use App\Enums\TimelineType;
use App\Models\Department;
use App\Models\Petition;
use App\Models\PetitionCategory;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\ArrayObject;
use Illuminate\Support\Facades\DB;

use function is_string;
use function preg_match;
use function sprintf;
use function str_ends_with;

#[Description('Add the "-W" suffix to the zaaknummer of every Woo/Wob objection (bezwaar) in a team')]
#[Signature('petitions:suffix-woo-objections
    {--department=wjz-bb : Slug of the team whose Woo/Wob objections get the suffix}
    {--commit : Commit changes to database (default is dry-run)}')]
class SuffixWooObjectionsCommand extends Command
{
    public const string SUFFIX = '-W';

    /**
     * Matches category names that start with the word "Woo" or "Wob" ("Woo", "Woo-verzoek (corona)", "Wob").
     */
    private const string CATEGORY_PATTERN = '/^wo[ob]\b/i';

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');
        /** @var string $slug */
        $slug = $this->option('department');

        $department = Department::query()->where('slug', $slug)->first();
        if (!$department) {
            $this->error(sprintf('Department "%s" not found', $slug));

            return self::FAILURE;
        }

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $categories = PetitionCategory::query()
            ->where('department_id', $department->id)
            ->orderBy('name')
            ->get()
            ->filter(static fn (PetitionCategory $category): bool => preg_match(self::CATEGORY_PATTERN, $category->name) === 1);

        if ($categories->isEmpty()) {
            $this->error(sprintf('No Woo/Wob categories found for department "%s"', $department->name));

            return self::FAILURE;
        }

        $this->info(sprintf('Matching categories: %s', $categories->implode('name', ', ')));

        $petitions = Petition::query()
            ->where('department_id', $department->id)
            ->whereHas(
                'petitionType',
                static fn (Builder $query) => $query->where('type', PetitionVariant::BEZWAAR->value),
            )
            ->whereIn('petition_category_id', $categories->pluck('id'))
            ->with(['petitionCategory', 'petitionStatus', 'relatedPetitions.timelineItems'])
            ->orderBy('number')
            ->get();

        $this->info(sprintf('Found %d Woo/Wob objection(s) in department "%s"', $petitions->count(), $department->name));

        $renamed = 0;
        $alreadySuffixed = 0;
        $conflicts = 0;
        $timelineReferencesUpdated = 0;

        DB::beginTransaction();

        foreach ($petitions as $petition) {
            if (str_ends_with((string) $petition->number, self::SUFFIX)) {
                $alreadySuffixed++;
                continue;
            }

            $oldNumber = $petition->number;
            $newNumber = $oldNumber . self::SUFFIX;
            if (Petition::query()->where('number', $newNumber)->exists()) {
                $this->warn(sprintf('  Skipping %s: %s already exists', $oldNumber, $newNumber));
                $conflicts++;
                continue;
            }

            $this->line(sprintf(
                '  %s -> %s (%s, %s)',
                $oldNumber,
                $newNumber,
                $petition->petitionCategory?->name,
                $petition->petitionStatus->status,
            ));
            $petition->update(['number' => $newNumber]);
            $renamed++;

            $timelineReferencesUpdated += $this->updateTimelineReferences($petition, $oldNumber, $newNumber);
        }

        if ($isDryRun) {
            DB::rollBack();
        } else {
            DB::commit();
        }

        $this->info(sprintf(
            '%s %d petition(s), %d already had the suffix, %d skipped because the new number exists',
            $isDryRun ? 'Would rename' : 'Renamed',
            $renamed,
            $alreadySuffixed,
            $conflicts,
        ));

        $this->info(sprintf(
            '%s %d timeline reference(s) on related petitions',
            $isDryRun ? 'Would update' : 'Updated',
            $timelineReferencesUpdated,
        ));

        return self::SUCCESS;
    }

    /**
     * PetitionAttachAction/PetitionDetachAction copy a petition's zaaknummer into the `subject` of a
     * REFERENCED_OCCURRENCE timeline item on any petition it's related to (via petition_petition), to
     * show e.g. "linked to zaak X" in that petition's history. Keep those references in sync with the rename.
     */
    private function updateTimelineReferences(Petition $petition, string $oldNumber, string $newNumber): int
    {
        $updated = 0;

        foreach ($petition->relatedPetitions as $relatedPetition) {
            foreach ($relatedPetition->timelineItems as $timelineItem) {
                if ($timelineItem->type !== TimelineType::REFERENCED_OCCURRENCE) {
                    continue;
                }

                $data = $timelineItem->data;
                if (($data['type'] ?? null) !== Occurrence::PETITION_TYPE->value) {
                    continue;
                }

                $subject = $data['subject'] ?? null;
                if (!is_string($subject) || $subject !== $oldNumber) {
                    continue;
                }

                $this->line(sprintf(
                    '    Timeline: reference to %s on petition %s -> %s',
                    $oldNumber,
                    $relatedPetition->number,
                    $newNumber,
                ));

                $newData = $data->getArrayCopy();
                $newData['subject'] = $newNumber;
                $timelineItem->data = new ArrayObject($newData);
                $timelineItem->save();
                $updated++;
            }
        }

        return $updated;
    }
}
