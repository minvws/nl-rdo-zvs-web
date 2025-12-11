CREATE TABLE "petition_statuses"
(
    "id" varchar(255) NOT NULL,
    "status" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "petition_statuses"
    owner TO "cts";

ALTER TABLE
    "petition_statuses"
    ADD
        PRIMARY KEY ("id");
