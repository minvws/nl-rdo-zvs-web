ALTER TABLE "users" ADD COLUMN "last_visited_department_id" uuid NULL;

ALTER TABLE "users"
    ADD CONSTRAINT "users_last_visited_department_id_foreign"
    FOREIGN KEY ("last_visited_department_id")
    REFERENCES "departments" ("id")
    ON DELETE SET NULL;
