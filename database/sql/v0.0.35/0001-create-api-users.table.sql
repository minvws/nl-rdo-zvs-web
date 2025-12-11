CREATE TABLE "api_users"
(
    "id" SERIAL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "api_key" char(64) NULL,
    "api_secret" text NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE
    "api_users" owner TO "cts";