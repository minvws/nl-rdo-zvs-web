UPDATE "processing_steps"
    SET "status" = 'pending'
    WHERE "status" = 'in_progress';

UPDATE "processing_steps"
    SET "status" = 'closed'
    WHERE "status" = 'completed';