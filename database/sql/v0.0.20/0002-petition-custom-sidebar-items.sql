CREATE TABLE "custom_petition_properties"
(
    "id" uuid NOT NULL PRIMARY KEY,
    "petition_type_id" uuid NOT NULL,
    "name" varchar(255) NOT NULL,
    "type" varchar(255) NOT NULL,
    "ordering" smallint NOT NULL,
    "created_at" TIMESTAMP(0) without time zone NULL,
    "updated_at" TIMESTAMP(0) without time zone NULL
);

ALTER TABLE
    "custom_petition_properties" owner TO "cts";

CREATE TABLE "custom_petition_property_petition"
(
    "custom_petition_property_id" uuid NOT NULL,
    "petition_id" uuid NOT NULL
);

ALTER TABLE
    "custom_petition_property_petition" owner TO "cts";
