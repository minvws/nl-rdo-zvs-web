ALTER TABLE petitions 
ADD COLUMN deadline_decision_period DATE DEFAULT NULL,
ADD COLUMN deadline_notice_of_default DATE DEFAULT NULL,
ADD COLUMN deadline_appeal_not_timely DATE DEFAULT NULL;

CREATE INDEX idx_petitions_deadline_decision_period ON petitions(deadline_decision_period);
CREATE INDEX idx_petitions_deadline_notice_of_default ON petitions(deadline_notice_of_default);
CREATE INDEX idx_petitions_deadline_appeal_not_timely ON petitions(deadline_appeal_not_timely);