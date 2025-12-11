ALTER TABLE
    "petition_statuses"
    ADD
        COLUMN default_status BOOLEAN DEFAULT false;

CREATE UNIQUE INDEX idx_single_true_your_column
    ON petition_statuses ((default_status IS TRUE))
    WHERE default_status IS TRUE;
