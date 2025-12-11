CREATE TABLE "attachments"
(
    "id" varchar(255) NOT NULL PRIMARY KEY,
    "source_id" varchar(255) NOT NULL,
    "source_type" varchar(255) NOT NULL,
    "disk" varchar(255) NOT NULL,
    "path" varchar(255) NOT NULL,
    "name" varchar(255) NOT NULL,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

ALTER TABLE
    "attachments"
    owner TO "cts";
