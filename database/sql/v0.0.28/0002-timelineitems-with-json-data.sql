ALTER TABLE "timeline_items"
    ADD COLUMN "type" VARCHAR(100);
ALTER TABLE "timeline_items"
    ADD COLUMN "data" jsonb;

UPDATE "timeline_items" SET type=timelineable_type;

ALTER TABLE timeline_items ALTER COLUMN timelineable_id DROP NOT NULL;
ALTER TABLE timeline_items ALTER COLUMN timelineable_type DROP NOT NULL;

ALTER TABLE attachments ALTER COLUMN source_id DROP NOT NULL;
ALTER TABLE attachments ALTER COLUMN source_type DROP NOT NULL;
