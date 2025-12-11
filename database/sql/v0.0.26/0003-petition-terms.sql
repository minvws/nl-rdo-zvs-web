CREATE TABLE "petition_terms"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "petition_id" uuid NOT NULL,
    "type" varchar(255) NOT NULL,
    "start_date" DATE NOT NULL,
    "end_date" DATE,
    "duration_in_days" INTEGER,
    "penalty_amount_in_euros" INTEGER,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "petition_terms"
    ADD CONSTRAINT "fk_petition_terms_petition_id" FOREIGN KEY ("petition_id") REFERENCES "petitions" ("id")
        ON DELETE CASCADE;


ALTER TABLE
    "petition_terms"
    owner TO "cts";
