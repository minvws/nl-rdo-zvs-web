ALTER TABLE "timeline_items"
    DROP CONSTRAINT "timeline_items_timelineable_type_check";

CREATE TABLE "status_occurrences"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "previous_status" varchar(255) NOT NULL,
    "current_status" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "status_occurrences"
    owner TO "cts";

DROP TABLE "occurrences";
