ALTER TABLE "notes"
    ALTER COLUMN "created_at" TYPE TIMESTAMP(0) with time zone,
    ALTER COLUMN "updated_at" TYPE TIMESTAMP(0) with time zone;

ALTER TABLE "departments"
    ALTER COLUMN "created_at" TYPE TIMESTAMP(0) with time zone,
    ALTER COLUMN "updated_at" TYPE TIMESTAMP(0) with time zone;