CREATE TABLE "petition_deliverables"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "petition_id" uuid NOT NULL,
    "type" varchar(255) NOT NULL,
    "deadline_at" DATE NOT NULL,
    "description" varchar(255) NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "petition_deliverables"
    ADD CONSTRAINT "fk_petition_deliverables_petition_id" FOREIGN KEY ("petition_id") REFERENCES "petitions" ("id")
        ON DELETE CASCADE;

ALTER TABLE
    "petition_deliverables"
    owner TO "cts";
