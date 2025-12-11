CREATE TABLE "user_roles"
(
    "id" varchar(255) NOT NULL,
    "user_id" varchar(255) NOT NULL,
    "role" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "user_roles"
    owner TO "cts";

ALTER TABLE
    "user_roles"
    ADD
        PRIMARY KEY ("id");
