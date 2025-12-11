ALTER TABLE user_roles RENAME TO user_global_roles;

ALTER TABLE
    "users"
    DROP COLUMN "is_admin";
