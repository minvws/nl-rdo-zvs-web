-- Migrate final_decision events to final_result with result_type = 'final_decision'
UPDATE petition_events
SET type = 'final_result', result_type = 'final_decision'
WHERE type = 'final_decision';

-- Migrate petition_withdrawn events to final_result with result_type = 'withdrawn'
UPDATE petition_events
SET type = 'final_result', result_type = 'withdrawn'
WHERE type = 'petition_withdrawn';