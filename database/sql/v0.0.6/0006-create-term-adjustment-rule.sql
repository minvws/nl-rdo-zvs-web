CREATE TABLE "term_adjustments"
(
    "id" VARCHAR(255) NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "period_in_days" integer NOT NULL DEFAULT 0,
    "is_law_applicable" boolean NOT NULL DEFAULT false,
    "can_enter_date_manually" boolean NOT NULL DEFAULT false,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "timeline_items"
    owner TO "cts";
