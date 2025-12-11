CREATE TABLE "case_requests"
(
    "id" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NULL,
    "updated_at" TIMESTAMP(0) with time zone NULL
);

ALTER TABLE
    "case_requests"
    owner TO "cts";

ALTER TABLE
    "case_requests"
    ADD
        PRIMARY KEY ("id");
