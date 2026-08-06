-- ResultType::PARTIAL_DECISION (mistyped in the database as 'partal_decision') was removed:
-- a partial decision is now its own PetitionEventType (`sent_partial_decision`) instead of a
-- result_type on a final_result event. Migrate existing rows to the new event type.
UPDATE petition_events
SET type = 'sent_partial_decision', result_type = NULL
WHERE type = 'final_result'
  AND result_type IN ('partal_decision', 'partial_decision');
