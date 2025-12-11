ALTER TABLE "timeline_items"
    ALTER COLUMN "user_id" DROP NOT NULL;

CREATE TABLE "deadline_adjustment_occurrence"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "previous_deadline" DATE NOT NULL,
    "current_deadline" DATE NOT NULL,
    "phase_name" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "deadline_adjustment_occurrence"
    OWNER TO "cts";
