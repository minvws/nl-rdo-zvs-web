CREATE TABLE "jobs"
(
    "id" bigserial NOT NULL PRIMARY KEY,
    "queue" varchar(255) NOT NULL,
    "payload" text NOT NULL,
    "attempts" smallint NOT NULL,
    "reserved_at" integer NULL,
    "available_at" integer NOT NULL,
    "created_at" integer NOT NULL
);

ALTER TABLE "jobs" owner TO "cts";

CREATE INDEX "jobs_queue_index" ON "jobs" ("queue");

CREATE TABLE "job_batches"
(
    "id" bigserial NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "total_jobs" integer NOT NULL,
    "pending_jobs" integer NOT NULL,
    "failed_jobs" integer NOT NULL,
    "failed_job_ids" text NOT NULL,
    "options" text NULL,
    "cancelled_at" integer NULL,
    "created_at" integer NOT NULL,
    "finished_at" integer NULL
);

ALTER TABLE "job_batches" owner TO "cts";

CREATE TABLE "failed_jobs"
(
    "id" bigserial NOT NULL PRIMARY KEY,
    "uuid" varchar(255) NOT NULL,
    "connection" text NOT NULL,
    "queue" text NOT NULL,
    "payload" text NOT NULL,
    "exception" text NOT NULL,
    "failed_at" TIMESTAMP(0) without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE "failed_jobs" owner TO "cts";

ALTER TABLE "failed_jobs" ADD
        CONSTRAINT "failed_jobs_uuid_unique" UNIQUE ("uuid");
