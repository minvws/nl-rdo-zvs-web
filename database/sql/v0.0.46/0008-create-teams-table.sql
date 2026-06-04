CREATE TABLE "teams" (
    "id" uuid NOT NULL PRIMARY KEY,
    "department_id" uuid NOT NULL,
    "name" varchar(64) NOT NULL,
    "active" boolean NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "teams" OWNER TO "cts";
