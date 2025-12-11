ALTER TABLE "deadline_occurrences" RENAME TO "term_adjustment_occurrences";

ALTER TABLE "term_adjustment_occurrences" RENAME COLUMN "reason_for_change" TO "name";

UPDATE "timeline_items" SET "timelineable_type" = 'App\Models\Database\DatabaseTermAdjustmentOccurrence' WHERE "timelineable_type" = 'App\Models\Database\DatabaseDeadlineOccurrence';
