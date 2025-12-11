UPDATE timeline_items
SET timelineable_type='active_phase_delete_occurrence'
WHERE timelineable_type='App\Models\Database\ActivePhaseDeleteOccurrence';

UPDATE timeline_items
SET timelineable_type='active_phase_occurrence'
WHERE timelineable_type='App\Models\Database\ActivePhaseOccurrence';

UPDATE timeline_items
SET timelineable_type='active_phase_rollback_occurrence'
WHERE timelineable_type='App\Models\Database\ActivePhaseRollbackOccurrence';

UPDATE timeline_items
SET timelineable_type='assignment_occurrence'
WHERE timelineable_type='App\Models\Database\AssignmentOccurrence';

UPDATE timeline_items
SET timelineable_type='deadline_adjustment_occurrence'
WHERE timelineable_type='App\Models\Database\DeadlineAdjustmentOccurrence';

UPDATE timeline_items
SET timelineable_type='note'
WHERE timelineable_type='App\Models\Database\Note';

UPDATE timeline_items
SET timelineable_type='status_occurrence'
WHERE timelineable_type='App\Models\Database\StatusOccurrence';

UPDATE timeline_items
SET timelineable_type='term_adjustment_occurrence'
WHERE timelineable_type='App\Models\Database\TermAdjustmentOccurrence';

UPDATE timeline_items
SET timelineable_type='decision_reference_occurrence'
WHERE timelineable_type='App\Models\Database\DecisionReferenceOccurrence';