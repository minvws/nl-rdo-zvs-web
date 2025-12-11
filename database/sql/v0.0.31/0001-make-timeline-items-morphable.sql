ALTER TABLE "timeline_items"
    ADD COLUMN "timelineable_type" varchar(50) DEFAULT 'petition' NOT NULL;
ALTER TABLE "timeline_items"
    RENAME COLUMN "petition_id" to "timelineable_id";
ALTER TABLE "timeline_items"
    ALTER COLUMN "timelineable_type" DROP DEFAULT;
