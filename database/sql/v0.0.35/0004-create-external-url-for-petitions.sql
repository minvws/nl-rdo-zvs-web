CREATE TABLE "petition_external_urls" (
    "id" UUID PRIMARY KEY,
    "petition_id" UUID NOT NULL,
    "petition_external_url_type" VARCHAR(255) NOT NULL,
    "url" VARCHAR(2048) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "petition_external_urls"
    OWNER TO "cts";

ALTER TABLE
    "petition_external_urls"
    ADD
        CONSTRAINT "petition_external_urls_petition_id_foreign" FOREIGN KEY ("petition_id") REFERENCES "petitions" ("id") ON DELETE CASCADE;
