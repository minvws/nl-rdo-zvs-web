ALTER TABLE petition_categories
    ALTER COLUMN name TYPE varchar(64)
        USING LEFT(name, 64);
ALTER TABLE petition_types
    ALTER COLUMN name TYPE varchar(64)
        USING LEFT(name, 64),
    ALTER COLUMN particularity_label TYPE varchar(16)
        USING LEFT(name, 16);
ALTER TABLE users
    ALTER COLUMN name TYPE varchar(128)
        USING LEFT(name, 128),
    ALTER COLUMN email TYPE varchar(128)
        USING LEFT(email, 128);
ALTER TABLE public_holidays
    ALTER COLUMN name TYPE varchar(64)
        USING LEFT(name, 64);

