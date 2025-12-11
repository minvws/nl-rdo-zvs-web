ALTER TABLE "contacts"
    ADD COLUMN "type" VARCHAR(50) NOT NULL DEFAULT 'civilian',
    DROP COLUMN "is_journalist";

ALTER TABLE "contact_petition"
    RENAME COLUMN "type" TO "role";