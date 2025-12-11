DELETE FROM timeline_items WHERE timelineable_type = 'decision_reference_occurrence';

ALTER TABLE petitions DROP COLUMN decision_references;

DROP TABLE decision_reference_occurrences;