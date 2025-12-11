ALTER TABLE users
    ALTER COLUMN email_verified_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING email_verified_at AT TIME ZONE 'UTC',
    ALTER COLUMN otp_confirmed_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING otp_confirmed_at AT TIME ZONE 'UTC',
    ALTER COLUMN created_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING created_at AT TIME ZONE 'UTC',
    ALTER COLUMN updated_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING updated_at AT TIME ZONE 'UTC';

ALTER TABLE failed_jobs
    ALTER COLUMN failed_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING failed_at AT TIME ZONE 'UTC';

ALTER TABLE personal_access_tokens
    ALTER COLUMN last_used_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING last_used_at AT TIME ZONE 'UTC',
    ALTER COLUMN expires_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING expires_at AT TIME ZONE 'UTC',
    ALTER COLUMN created_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING created_at AT TIME ZONE 'UTC',
    ALTER COLUMN updated_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING updated_at AT TIME ZONE 'UTC';

ALTER TABLE password_reset_tokens
    ALTER COLUMN created_at
        TYPE TIMESTAMP WITH TIME ZONE
        USING created_at AT TIME ZONE 'UTC';
