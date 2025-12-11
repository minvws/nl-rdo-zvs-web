CREATE TABLE "timeline_items"
(
    "internal_id" serial PRIMARY KEY,
    "petition_id" varchar(255) NOT NULL,
    "user_id" varchar(255),
    "timelineable_id" varchar(255) NOT NULL,
    "timelineable_type" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "timeline_items"
    ADD CONSTRAINT
        "timeline_items_timelineable_type_check" CHECK
            ("timelineable_type" = 'App\Models\Database\DatabaseNote'
                 OR
             "timelineable_type" = 'App\Models\Database\DatabaseDeadlineOccurrence');

ALTER TABLE
    "timeline_items"
    owner TO "cts";
