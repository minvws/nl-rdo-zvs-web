CREATE TABLE "public_holidays"
(
    "id" varchar(255) NOT NULL PRIMARY KEY ,
    "name" varchar(255) NOT NULL,
    "date" date NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

CREATE INDEX idx_public_holidays_date ON "public_holidays" ("date");

ALTER TABLE
    "public_holidays"
    owner TO "cts";
