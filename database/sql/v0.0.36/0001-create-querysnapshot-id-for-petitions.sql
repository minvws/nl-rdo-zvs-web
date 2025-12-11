CREATE TABLE "petition_querysnapshots" (
    "id" UUID PRIMARY KEY,
    "petition_id" UUID NOT NULL,
    "querysnapshot_id" VARCHAR(255) NOT NULL,
    "querysnapshot_type" VARCHAR(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "petition_querysnapshots"
    OWNER TO "cts";

ALTER TABLE
    "petition_querysnapshots"
    ADD
        CONSTRAINT "petition_querysnapshots_petition_id_foreign" FOREIGN KEY ("petition_id") REFERENCES "petitions" ("id") ON DELETE CASCADE;
