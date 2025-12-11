CREATE TABLE "contacts"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "initials" varchar(255),
    "last_name" varchar(255),
    "organisation_name" varchar(255),
    "email_address" varchar(255),
    "phone_number" varchar(255),
    "street" varchar(255),
    "house_number" varchar(255),
    "postal_code" varchar(255),
    "city" varchar(255),
    "is_journalist" bool default false,
    "created_at" TIMESTAMP(0) with time zone NOT NULL,
    "updated_at" TIMESTAMP(0) with time zone NOT NULL
);

/** the pivot table for the many-to-many relationship */
CREATE TABLE "contact_petition"
(
    "contact_id" uuid NOT NULL,
    "petition_id" uuid NOT NULL,
    "type" varchar(255) NOT NULL
);

ALTER TABLE
    "contacts"
    owner TO "cts";

ALTER TABLE
    "contact_petition"
    owner TO "cts";