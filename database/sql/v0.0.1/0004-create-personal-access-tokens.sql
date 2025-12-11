CREATE TABLE "personal_access_tokens"
(
    "id" bigserial NOT NULL PRIMARY KEY,
    "tokenable_type" varchar(255) NOT NULL,
    "tokenable_id" bigint NOT NULL,
    "name" varchar(255) NOT NULL,
    "token" varchar(64) NOT NULL,
    "abilities" text NULL,
    "last_used_at" TIMESTAMP(0) without time zone NULL,
    "expires_at" TIMESTAMP(0) without time zone NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE
    "personal_access_tokens"
    owner TO "cts";

CREATE INDEX "personal_access_tokens_tokenable_type_tokenable_id_index" ON "personal_access_tokens" (
    "tokenable_type",
    "tokenable_id"
);

ALTER TABLE
    "personal_access_tokens"
    ADD
        CONSTRAINT "personal_access_tokens_token_unique" UNIQUE ("token");
