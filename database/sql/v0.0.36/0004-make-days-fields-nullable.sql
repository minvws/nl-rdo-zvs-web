ALTER TABLE "petition_draft_terms"
    ALTER COLUMN  "days_after_event" DROP NOT NULL,
    ALTER COLUMN  "days_after_date_withdrawal" SET DEFAULT 0;
