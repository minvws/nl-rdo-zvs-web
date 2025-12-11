ALTER TABLE "active_phases"
    ALTER COLUMN "petition_id" TYPE uuid
        USING petition_id::uuid;
ALTER TABLE "active_phase_occurrences"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "attachments"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid,
    ALTER COLUMN "source_id" TYPE uuid
        USING "source_id"::uuid;
ALTER TABLE "assignment_occurrences"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "notes"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "petitions"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid,
    ALTER COLUMN "requesting_party_id" TYPE uuid
        USING "requesting_party_id"::uuid,
    ALTER COLUMN "petition_status_id" TYPE uuid
        USING "petition_status_id"::uuid,
    ALTER COLUMN "petition_type_id" TYPE uuid
        USING "petition_type_id"::uuid;
ALTER TABLE "petition_statuses"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "petition_types"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "petition_type_phase"
    ALTER COLUMN "phase_id" TYPE uuid
        USING "phase_id"::uuid,
    ALTER COLUMN "petition_type_id" TYPE uuid
        USING "petition_type_id"::uuid;
ALTER TABLE "phases"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "public_holidays"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "requesting_parties"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "status_occurrences"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "timeline_items"
    ALTER COLUMN "petition_id" TYPE uuid
        USING "petition_id"::uuid,
    ALTER COLUMN "user_id" TYPE uuid
        USING "user_id"::uuid,
    ALTER COLUMN "timelineable_id" TYPE uuid
        USING "timelineable_id"::uuid;
ALTER TABLE "term_adjustment_occurrences"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
ALTER TABLE "term_adjustments"
    ALTER COLUMN "id" TYPE uuid
        USING "id"::uuid;
