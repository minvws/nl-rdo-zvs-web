CREATE TABLE "password_reset_tokens"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "email" varchar(255) NOT NULL,
    "token" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) without time zone NOT NULL
);

ALTER TABLE
    "password_reset_tokens"
    owner TO "cts";

CREATE INDEX "password_reset_tokens_email_index" ON "password_reset_tokens" (
    "email"
);
