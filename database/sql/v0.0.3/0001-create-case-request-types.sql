CREATE TABLE "case_request_types"
(
    "id" varchar(255) NOT NULL,
    "name" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "case_request_types"
    owner TO "cts";

ALTER TABLE
    "case_request_types"
    ADD
        PRIMARY KEY ("id");
