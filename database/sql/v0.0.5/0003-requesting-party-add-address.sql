ALTER TABLE
    "requesting_parties"
    RENAME COLUMN "name" TO "firstname";

ALTER TABLE
    "requesting_parties"
    ADD COLUMN "phonenumber" varchar(255) NULL,
    ADD COLUMN "lastname" varchar(255) NULL,
    ADD COLUMN "lastname_prefix" varchar(255) NULL,
    ADD COLUMN "street" varchar(255) NULL,
    ADD COLUMN "housenumber" varchar(255) NULL,
    ADD COLUMN "postalcode" varchar(255) NULL,
    ADD COLUMN "city" varchar(255) NULL,
    ADD COLUMN "country" varchar(255) NULL;

UPDATE "requesting_parties"
    SET "firstname" = ''
    WHERE "firstname" IS NULL;

UPDATE "requesting_parties"
    SET "lastname" = ''
    WHERE "lastname" IS NULL;

ALTER TABLE requesting_parties
    ALTER COLUMN "firstname"
        SET NOT NULL;

ALTER TABLE requesting_parties
    ALTER COLUMN "lastname"
        SET NOT NULL;

ALTER TABLE requesting_parties
    ALTER COLUMN "email"
        DROP NOT NULL;
