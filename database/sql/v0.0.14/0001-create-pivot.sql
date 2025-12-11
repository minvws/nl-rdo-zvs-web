CREATE TABLE "department_user"
(
    "department_id" uuid NOT NULL,
    "user_id" uuid NOT NULL,
    "role" varchar(255) NOT NULL
);

ALTER TABLE
    "department_user"
    owner TO "cts";
