CREATE TABLE "users"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "name" varchar(255) NOT NULL,
    "email" varchar(255) NOT NULL,
    "email_verified_at" TIMESTAMP(0) without time zone NULL,
    "password" varchar(255) NULL,
    "otp_confirmed_at" TIMESTAMP(0) without time zone NULL,
    "otp_recovery_codes" text NULL,
    "otp_secret" text NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE
    "users" owner TO "cts";

ALTER TABLE
    "users"
    ADD
        CONSTRAINT "users_email_unique" UNIQUE ("email");
