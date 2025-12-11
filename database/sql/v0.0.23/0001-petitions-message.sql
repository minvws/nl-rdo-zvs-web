ALTER TABLE "petitions"
    RENAME COLUMN "appeal" TO "message";

ALTER TABLE "petitions"
    RENAME COLUMN "date_of_appeal" TO "date_of_message";
