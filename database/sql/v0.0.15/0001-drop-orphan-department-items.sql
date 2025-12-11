-- A file for removing orphan department items. Which are department items that have not yet been set with a department id.
-- Version: Mon 28 Oct 12:27
-- DB version: develop

DELETE
FROM "contact_petition" AS "cp" USING "contacts" as "c"
WHERE "cp"."contact_id" = "c"."id"
  AND "c"."department_id" IS NULL;

DELETE
FROM "contacts"
WHERE "department_id" IS NULL;

DELETE
FROM "petition_type_phase" AS "ptp" USING "petition_types" as "pt"
WHERE "pt"."id" = "ptp"."petition_type_id"
  AND "pt"."department_id" IS NULL;

DELETE
FROM "petitions" AS "p" USING "petition_types" as "pt"
WHERE "pt"."id" = "p"."petition_type_id"
  AND "pt"."department_id" IS NULL;

DELETE
FROM "petition_types"
WHERE "department_id" IS NULL;

DELETE
FROM "active_phases" AS "ap" USING "petitions" as "p"
WHERE "p"."id" = "ap"."petition_id"
  AND "p"."department_id" IS NULL;

DELETE
FROM "active_phase_delete_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
   )
);

DELETE
FROM "active_phase_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
    )
);

DELETE
FROM "active_phase_rollback_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
    )
);

DELETE
FROM "assignment_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
    )
);

DELETE
FROM "deadline_adjustment_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
    )
);

DELETE
FROM "status_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
    )
);

DELETE
FROM "term_adjustment_occurrences" as "oc"
WHERE "oc"."id" IN (
    SELECT "ti"."timelineable_id"
    FROM "timeline_items" AS "ti"
    WHERE "ti"."petition_id" IN (
        SELECT "pet"."id"
        FROM "petitions" as "pet"
        WHERE "pet"."department_id" IS NULL
    )
);

DELETE
FROM "timeline_items" AS "ti" USING "petitions" as "p"
WHERE "p"."id" = "ti"."petition_id"
  AND "p"."department_id" IS NULL;

DELETE
FROM "petitions"
WHERE "department_id" IS NULL;

DELETE
FROM "petition_type_phase" AS "ptp" USING "phases" as "p"
WHERE "p"."id" = "ptp"."phase_id"
  AND "p"."department_id" IS NULL;

DELETE
FROM "phases"
WHERE "department_id" IS NULL;

DELETE
FROM "term_adjustments"
WHERE "department_id" IS NULL;
