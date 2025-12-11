CREATE INDEX petition_related_petition_index
    ON petition_petition (petition_id, related_petition_id);

CREATE INDEX related_petition_petition_index
    ON petition_petition (related_petition_id, petition_id);
