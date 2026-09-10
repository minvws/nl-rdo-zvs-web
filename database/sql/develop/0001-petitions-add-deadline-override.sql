ALTER TABLE "petitions"
    ADD COLUMN "deadline_override_at" DATE NULL,
    ADD COLUMN "deadline_override_reason" TEXT NULL;

CREATE INDEX petition_effective_deadline_idx
    ON "petitions" ((COALESCE("deadline_override_at", "deadline_at")));
