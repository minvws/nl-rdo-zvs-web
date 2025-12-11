--- Create web user
DO
$do$
    BEGIN
        IF EXISTS (SELECT
                   FROM pg_catalog.pg_roles
                   WHERE rolname = 'cts') THEN
            RAISE NOTICE 'Role "cts" already exists. Skipping.';
        ELSE
            CREATE ROLE cts;
        END IF;
    END
$do$;

ALTER ROLE cts WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS;

--- Create DBA role
DO
$do$
    BEGIN
        IF EXISTS (SELECT
                   FROM pg_catalog.pg_roles
                   WHERE rolname = 'cts_dba') THEN
            RAISE NOTICE 'Role "cts_dba" already exists. Skipping.';
        ELSE
            CREATE ROLE cts_dba;
        END IF;
    END
$do$;

ALTER ROLE cts_dba WITH NOSUPERUSER INHERIT NOCREATEROLE NOCREATEDB LOGIN NOREPLICATION NOBYPASSRLS;

-- create deploy_releases
CREATE TABLE deploy_releases
(
    version varchar(255),
    deployed_at timestamp default now()
);

ALTER TABLE deploy_releases OWNER TO cts_dba;

GRANT SELECT ON deploy_releases TO cts;

INSERT INTO deploy_releases
    values ('v0.0.0', '2000-01-01 00:00:00');

-- create migrations

CREATE SEQUENCE IF NOT EXISTS migrations_id_seq;

CREATE TABLE "migrations"
(
    "id" int4 NOT NULL DEFAULT nextval('migrations_id_seq'::regclass),
    "migration" varchar(255) NOT NULL,
    "batch" int4 NOT NULL,
    PRIMARY KEY ("id")
);

ALTER TABLE "migrations"
    OWNER TO cts_dba;
