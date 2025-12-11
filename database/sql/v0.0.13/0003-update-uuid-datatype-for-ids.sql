ALTER TABLE "active_phase_rollback_occurences"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "deadline_adjustment_occurrence"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;

ALTER TABLE active_phase_delete_occurences RENAME TO active_phase_delete_occurrences;
ALTER TABLE active_phase_rollback_occurences RENAME TO active_phase_rollback_occurrences;
ALTER TABLE deadline_adjustment_occurrence RENAME TO deadline_adjustment_occurrences;

UPDATE timeline_items
SET timelineable_type='App\Models\Database\DatabaseActivePhaseDeleteOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseActivePhaseDeleteOccurence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\DatabaseActivePhaseRollbackOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseActivePhaseRollbackOccurence';
