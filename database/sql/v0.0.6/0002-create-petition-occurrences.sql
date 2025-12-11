CREATE TABLE "occurrences"
(
    "id" varchar(255) NOT NULL PRIMARY KEY ,
    "petition_id" varchar(255) NOT NULL,
    "user_id" varchar(255),
    "type" varchar(255) NOT NULL,
    "explanation" text,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "occurrences"
    owner TO "cts";
