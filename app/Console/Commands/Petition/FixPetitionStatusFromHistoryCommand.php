<?php

declare(strict_types=1);

namespace App\Console\Commands\Petition;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

use function array_map;
use function implode;
use function sprintf;
use function str_replace;

#[Signature('petitions:fix-status-from-history
    {zaaknummer? : Only show details for this single petition (implies dry-run)}
    {--commit : Commit changes to database (default is dry-run)}')]
#[Description('Set petition_status_id to the status from the most recent non-future history entry (with timeline) where they differ')]
class FixPetitionStatusFromHistoryCommand extends Command
{
    /**
     * Petition numbers (zaaknummers) that must never be modified by this command.
     */
    private const array SKIP_NUMBERS = [
        '2022.041',
        '2024.073',
        '2025.072',
        '2021000924HB',
        '2023000581VOVO',
        '2025W00170',
        '25 1295BER',
        '2025W00217',
        '2023001361BER',
        '2023000865BER',
        '2023001378BER',
    ];

    /**
     * Subquery that returns the most recent non-future history entry per petition,
     * restricted to entries that have a matching status_occurrence timeline item.
     */
    private const string LATEST_HISTORY_SUBQUERY = "
        SELECT DISTINCT ON (h.petition_id)
            h.petition_id,
            h.petition_status_id
        FROM petition_statuses_history_entries h
        WHERE h.date <= CURRENT_DATE
          AND EXISTS (
              SELECT 1
              FROM timeline_items ti
              JOIN petition_statuses ps ON ps.id = h.petition_status_id
              WHERE ti.timelineable_id::uuid = h.petition_id
                AND ti.timelineable_type = 'petition'
                AND ti.type = 'status_occurrence'
                AND (
                    ti.data->>'current_status' = ps.status
                    OR (ti.data->>'current_status' = 'Eind uitspraak' AND ps.status = 'Uitspraak')
                )
          )
        ORDER BY h.petition_id, h.date DESC, h.created_at DESC
    ";

    /** @var string $signature */
    protected $signature = 'petitions:fix-status-from-history
                            {zaaknummer? : Only show details for this single petition (implies dry-run)}
                            {--commit : Commit changes to database (default is dry-run)}';

    /** @var string $description */
    protected $description = 'Set petition_status_id to the status from the most recent non-future history entry (with timeline) where they differ';

    public function handle(): int
    {
        /** @var string|null $zaaknummer */
        $zaaknummer = $this->argument('zaaknummer');
        $isDryRun = $zaaknummer !== null || !$this->option('commit');

        if ($zaaknummer !== null) {
            $this->warn(sprintf('SINGLE PETITION MODE - Showing details for zaaknummer: %s', $zaaknummer));
        } elseif ($isDryRun) {
            $this->warn('DRY RUN MODE - No changes will be made to the database');
        }

        $zaaknummerFilter = $zaaknummer !== null ? 'AND p.number = :zaaknummer' : '';
        $bindings = $zaaknummer !== null ? ['zaaknummer' => $zaaknummer] : [];

        try {
            /** @var int $count */
            $count = DB::scalar(
                "
                SELECT COUNT(*)
                FROM petitions p
                JOIN (" . self::LATEST_HISTORY_SUBQUERY . ") AS latest ON p.id = latest.petition_id
                JOIN petition_statuses AS latest_ps ON latest.petition_status_id = latest_ps.id
                LEFT JOIN petition_statuses AS current_ps ON p.petition_status_id = current_ps.id
                JOIN departments AS d ON d.id = p.department_id
                WHERE latest.petition_status_id IS NOT NULL
                  AND latest_ps.petition_type_id = p.petition_type_id
                  AND current_ps.status IS DISTINCT FROM latest_ps.status
                  AND p.archived_at IS NULL
                  AND NOT (current_ps.status = 'BOB verzonden' AND d.name = 'WJZ Afdeling Bezwaar en Beroep')
                  AND NOT (current_ps.status = 'Intake' AND latest_ps.status = 'Toebedeling')
                  " . $this->skipNumbersFilter() . "
                  " . $zaaknummerFilter,
                $bindings,
            );

            if ($count === 0) {
                $this->info('No mismatched petitions found. Nothing to update.');

                return self::SUCCESS;
            }

            $this->info(sprintf('Found %d petition(s) with a mismatched status.', $count));

            $this->renderMismatchTable($zaaknummerFilter, $bindings);

            if ($isDryRun) {
                if ($zaaknummer !== null) {
                    return self::SUCCESS;
                }

                $this->info(sprintf('Would update %d petition(s). Run with --commit to apply changes.', $count));

                return self::SUCCESS;
            }

            DB::beginTransaction();

            $updated = DB::update("
                UPDATE petitions
                SET petition_status_id = latest.petition_status_id
                FROM (" . self::LATEST_HISTORY_SUBQUERY . ") AS latest
                JOIN petition_statuses AS latest_ps ON latest_ps.id = latest.petition_status_id
                WHERE petitions.id = latest.petition_id
                  AND latest.petition_status_id IS NOT NULL
                  AND latest_ps.petition_type_id = petitions.petition_type_id
                  AND latest_ps.status IS DISTINCT FROM (
                      SELECT ps.status FROM petition_statuses ps WHERE ps.id = petitions.petition_status_id
                  )
                  AND petitions.archived_at IS NULL
                  AND NOT EXISTS (
                      SELECT 1
                      FROM petition_statuses ps
                      JOIN departments d ON d.id = petitions.department_id
                      WHERE ps.id = petitions.petition_status_id
                        AND ps.status = 'BOB verzonden'
                        AND d.name = 'WJZ Afdeling Bezwaar en Beroep'
                  )
                  AND NOT (
                      latest_ps.status = 'Toebedeling'
                      AND EXISTS (
                          SELECT 1 FROM petition_statuses ps
                          WHERE ps.id = petitions.petition_status_id
                            AND ps.status = 'Intake'
                      )
                  )
                  " . $this->skipNumbersFilter('petitions') . "
            ");

            DB::commit();

            $this->info(sprintf('Successfully updated %d petition(s).', $updated));

            return self::SUCCESS;
        } catch (Throwable $e) {
            if (!$isDryRun) {
                DB::rollBack();
            }
            $this->error(sprintf('Error fixing petition statuses: %s', $e->getMessage()));

            return self::FAILURE;
        }
    }

    /**
     * Returns a SQL fragment that excludes all petition numbers in SKIP_NUMBERS.
     * Safe to inline: values are compile-time constants, not user input.
     */
    private function skipNumbersFilter(string $tableAlias = 'p'): string
    {
        $quoted = implode(',', array_map(
            static fn(string $n): string => "'" . str_replace("'", "''", $n) . "'",
            self::SKIP_NUMBERS,
        ));

        return sprintf('AND %s.number NOT IN (%s)', $tableAlias, $quoted);
    }

    /**
     * @param array<string, string> $bindings
     */
    private function renderMismatchTable(string $zaaknummerFilter, array $bindings): void
    {
        /** @var array<object{number: string, petition_status: string|null, history_status: string, new_status: string}> $rows */
        $rows = DB::select(
            "
            SELECT
                p.number,
                current_ps.status AS petition_status,
                history_ps.status AS history_status,
                history_ps.status AS new_status
            FROM petitions p
            JOIN (" . self::LATEST_HISTORY_SUBQUERY . ") AS latest ON p.id = latest.petition_id
            LEFT JOIN petition_statuses AS current_ps ON p.petition_status_id = current_ps.id
            JOIN petition_statuses AS history_ps ON latest.petition_status_id = history_ps.id
            JOIN departments AS d ON d.id = p.department_id
            WHERE latest.petition_status_id IS NOT NULL
              AND history_ps.petition_type_id = p.petition_type_id
              AND current_ps.status IS DISTINCT FROM history_ps.status
              AND p.archived_at IS NULL
              AND NOT (current_ps.status = 'BOB verzonden' AND d.name = 'WJZ Afdeling Bezwaar en Beroep')
              AND NOT (current_ps.status = 'Intake' AND history_ps.status = 'Toebedeling')
              " . $this->skipNumbersFilter() . "
              " . $zaaknummerFilter . "
            ORDER BY p.number
            ",
            $bindings,
        );

        $this->table(
            ['Zaaknummer', 'Current status (petitions)', 'Latest status (history)', 'New status after run'],
            array_map(
                static fn (object $row): array => [
                    $row->number,
                    $row->petition_status ?? '(none)',
                    $row->history_status,
                    $row->new_status,
                ],
                $rows,
            ),
        );
    }
}
