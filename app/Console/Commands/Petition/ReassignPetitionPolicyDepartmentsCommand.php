<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use App\Models\PolicyDepartment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function array_keys;
use function sprintf;

#[Description('Reassign petitions from one policy department to another based on a fixed mapping list')]
#[Signature('petitions:reassign-policy-departments
    {--commit : Commit changes to database (default is dry-run)}')]
class ReassignPetitionPolicyDepartmentsCommand extends Command
{
    private const string PIVOT_TABLE = 'petition_policy_department';

    /** @var array<string, string> */
    private array $mappings = [
        'Sport' => 'Directie Sport',
        'COVID-19' => 'PD COVID-19',
        'DLV' => 'LZ',
    ];

    public function handle(): int
    {
        $isDryRun = !$this->option('commit');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $rows = [];
        $hasError = false;
        $resolved = [];

        foreach ($this->mappings as $from => $to) {
            $fromDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', $from)->first();
            $toDept = PolicyDepartment::query()->withoutGlobalScopes()->where('name', $to)->first();

            if ($fromDept === null) {
                $rows[] = [$from, $to, 0, 'No from-department found'];
                $hasError = true;
                continue;
            }

            if ($toDept === null) {
                $rows[] = [$from, $to, 0, 'No to-department found'];
                $hasError = true;
                continue;
            }

            $count = DB::table(self::PIVOT_TABLE)
                ->where('policy_department_id', $fromDept->id)
                ->count();

            $rows[] = [$from, $to, $count, 'OK'];
            $resolved[$from] = [$fromDept, $toDept];
        }

        $this->table(
            ['From department', 'To department', 'Petitions', 'Status'],
            $rows,
        );

        if ($isDryRun) {
            $this->info('Run with --commit to apply the changes.');

            return self::SUCCESS;
        }

        if ($hasError) {
            $this->error('Some departments could not be resolved. Fix the issues above before committing.');

            return self::FAILURE;
        }

        try {
            DB::beginTransaction();

            $totalReassigned = 0;

            foreach (array_keys($this->mappings) as $from) {
                [$fromDept, $toDept] = $resolved[$from];

                // Petitions that already have the "to" department: just remove "from"
                $alreadyHaveTo = DB::table(self::PIVOT_TABLE)
                    ->where('policy_department_id', $toDept->id)
                    ->pluck('petition_id');

                DB::table(self::PIVOT_TABLE)
                    ->where('policy_department_id', $fromDept->id)
                    ->whereIn('petition_id', $alreadyHaveTo)
                    ->delete();

                // Petitions that only have "from": replace with "to"
                $updated = DB::table(self::PIVOT_TABLE)
                    ->where('policy_department_id', $fromDept->id)
                    ->update(['policy_department_id' => $toDept->id]);

                $totalReassigned += $updated;
            }

            DB::commit();

            $this->info(sprintf('Successfully reassigned %d petition(s).', $totalReassigned));

            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(sprintf('Error reassigning policy departments: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }
}
