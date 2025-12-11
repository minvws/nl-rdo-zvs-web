ALTER TABLE attachments DROP COLUMN source_id;
ALTER TABLE attachments DROP COLUMN source_type;

ALTER TABLE timeline_items DROP COLUMN timelineable_id;
ALTER TABLE timeline_items DROP COLUMN timelineable_type;

DROP table assignment_occurrences;
DROP table deadline_adjustment_occurrences;
DROP table notes;
DROP table status_occurrences;
DROP table referenced_occurrences;
