CREATE TABLE user_department_filters (
    id UUID PRIMARY KEY,
    user_id UUID NOT NULL,
    department_id UUID NOT NULL,
    filterable_type VARCHAR(255) NOT NULL,
    filter_data JSONB NOT NULL,
    created_at TIMESTAMP(0) with time zone NULL DEFAULT NOW(),
    updated_at TIMESTAMP(0) with time zone NULL DEFAULT NOW(),

    CONSTRAINT fk_user_department_filters_user_id
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_department_filters_department_id
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    CONSTRAINT uk_user_department_filters_unique
        UNIQUE (user_id, department_id, filterable_type)
);

ALTER TABLE
    user_department_filters
    owner TO "cts";
