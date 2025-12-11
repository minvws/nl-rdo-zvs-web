CREATE TABLE notes (
    id VARCHAR(255) NOT NULL PRIMARY KEY,
    petition_id varchar(255) NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP WITH TIME ZONE NOT NULL,
    updated_at TIMESTAMP WITH TIME ZONE NOT NULL,
    authored_by VARCHAR(255) NOT NULL
);
alter table notes
    OWNER TO cts;

