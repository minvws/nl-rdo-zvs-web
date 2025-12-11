CREATE TABLE notifications (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    type VARCHAR(255) NOT NULL,
    notifiable_type VARCHAR(255) NOT NULL,
    notifiable_id UUID NOT NULL,
    data JSONB NOT NULL,
    read_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
    created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
);

ALTER TABLE notifications
    OWNER TO cts;

CREATE INDEX notifications_notifiable_type_notifiable_id_index
    ON notifications (notifiable_type, notifiable_id);
