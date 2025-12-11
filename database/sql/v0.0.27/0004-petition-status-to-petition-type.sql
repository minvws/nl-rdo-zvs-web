TRUNCATE petition_statuses;

ALTER TABLE petition_statuses
    DROP COLUMN department_id;

ALTER TABLE petition_statuses
    ADD COLUMN "petition_type_id" uuid NOT NULL;
