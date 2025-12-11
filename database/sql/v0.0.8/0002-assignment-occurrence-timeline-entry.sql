CREATE TABLE "assignment_occurrences"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "previous_assigned_user_name" varchar(255),
    "current_assigned_user_name" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "assignment_occurrences"
    owner TO "cts";

