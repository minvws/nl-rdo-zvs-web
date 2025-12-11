CREATE TABLE "petition_exports" (
    "id" uuid NOT NULL PRIMARY KEY,
    "department_id" uuid NOT NULL,
    "created_at" TIMESTAMP WITH TIME ZONE NOT NULL,
    "updated_at" TIMESTAMP WITH TIME ZONE NOT NULL,
    "petition_type_id" uuid NOT NULL,
    "date_from" date NOT NULL,
    "date_to" date NOT NULL,
    "disk" varchar(255) NOT NULL,
    "path" varchar(255) NOT NULL,
    "filters" varchar(255) NOT NULL
);

ALTER TABLE
    "petition_exports"
    owner TO "cts";