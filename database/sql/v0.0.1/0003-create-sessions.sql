CREATE TABLE "sessions"
(
    "id" varchar(255) NOT NULL,
    "user_id" uuid NULL,
    "ip_address" varchar(45) NULL,
    "user_agent" text NULL,
    "payload" text NOT NULL,
    "last_activity" integer NOT NULL
);

ALTER TABLE "sessions" owner TO "cts";

ALTER TABLE "sessions" ADD
  CONSTRAINT "sessions_user_id_foreign" FOREIGN KEY ("user_id") REFERENCES "users" ("id");

ALTER TABLE "sessions" ADD PRIMARY KEY ("id");

CREATE INDEX "sessions_user_id_index" ON "sessions" ("user_id");

CREATE INDEX "sessions_last_activity_index" ON "sessions" ("last_activity");
