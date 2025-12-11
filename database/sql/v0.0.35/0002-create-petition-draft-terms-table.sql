CREATE TABLE "petition_draft_terms"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "petition_id" uuid NOT NULL,
    "description" VARCHAR(255) COLLATE "en_US.utf8" NULL,
    "start_date" DATE NOT NULL,
    "event_date" DATE NULL,
    "days_after_event" INTEGER NOT NULL DEFAULT 0,
    "date_withdrawal" DATE NULL,
    "days_after_date_withdrawal" INTEGER NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE
    "petition_draft_terms" owner TO "cts";

ALTER TABLE
    "petition_draft_terms"
    ADD
        CONSTRAINT "petition_draft_terms_petition_id_foreign" FOREIGN KEY ("petition_id") REFERENCES "petitions" ("id") ON DELETE CASCADE;
