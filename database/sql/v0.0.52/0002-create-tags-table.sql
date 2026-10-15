CREATE TABLE "tags" (
    "id" uuid NOT NULL PRIMARY KEY,
    "department_id" uuid NOT NULL,
    "name" varchar(64) NOT NULL,
    "color" varchar(16) NOT NULL,
    "description" varchar(255),
    "active" boolean NOT NULL DEFAULT true,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE "tags" OWNER TO "cts";

ALTER TABLE "tags"
    ADD CONSTRAINT fk_tags_department_id
    FOREIGN KEY (department_id) REFERENCES departments (id)
    ON DELETE RESTRICT;

CREATE UNIQUE INDEX "tags_department_id_lower_name_unique"
    ON "tags" (department_id, lower(name));
