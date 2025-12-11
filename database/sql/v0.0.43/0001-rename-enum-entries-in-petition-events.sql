UPDATE petition_events SET type =
    CASE type
        WHEN 'committee_hearing_scheduled' THEN 'meeting_scheduled'
    END
WHERE type IN ('committee_hearing_scheduled');