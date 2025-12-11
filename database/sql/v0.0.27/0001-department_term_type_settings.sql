CREATE TABLE "department_term_type_settings"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "department_id" uuid NOT NULL,
    "term_type" varchar(255) NOT NULL,
    "field" varchar(255) NOT NULL,
    "active" boolean NOT NULL,
    "default_value" varchar(255) NULL
);

ALTER TABLE "department_term_type_settings"
ADD CONSTRAINT "department_term_type_settings_department_id" FOREIGN KEY ("department_id") REFERENCES "departments" ("id")
ON DELETE CASCADE;

ALTER TABLE "department_term_type_settings"
ADD CONSTRAINT "department_term_type_settings_deparment_term_type_field_unique" unique ("department_id", "term_type", "field");

ALTER TABLE
    "department_term_type_settings"
    owner TO "cts";
