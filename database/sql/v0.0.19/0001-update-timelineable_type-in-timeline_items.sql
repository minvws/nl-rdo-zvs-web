UPDATE timeline_items
SET timelineable_type='App\Models\Database\ActivePhaseDeleteOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseActivePhaseDeleteOccurrence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\ActivePhaseOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseActivePhaseOccurrence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\ActivePhaseRollbackOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseActivePhaseRollbackOccurrence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\AssignmentOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseAssignmentOccurrence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\DeadlineAdjustmentOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseDeadlineAdjustmentOccurrence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\Note'
WHERE timelineable_type='App\Models\Database\DatabaseNote';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\StatusOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseStatusOccurrence';

UPDATE timeline_items
SET timelineable_type='App\Models\Database\TermAdjustmentOccurrence'
WHERE timelineable_type='App\Models\Database\DatabaseTermAdjustmentOccurrence';
