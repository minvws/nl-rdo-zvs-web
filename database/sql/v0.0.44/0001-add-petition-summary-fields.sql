ALTER TABLE petitions ADD COLUMN total_days_suspended INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN igs_penalty_today INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN bnt_penalty_today INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN igs_forfeited INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN bnt_forfeited INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN igs_penalty_maximum INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN bnt_penalty_maximum INTEGER NOT NULL DEFAULT 0;

CREATE INDEX idx_petitions_total_days_suspended ON petitions(total_days_suspended);
CREATE INDEX idx_petitions_igs_penalty_today ON petitions(igs_penalty_today);
CREATE INDEX idx_petitions_bnt_penalty_today ON petitions(bnt_penalty_today);
CREATE INDEX idx_petitions_igs_forfeited ON petitions(igs_forfeited);
CREATE INDEX idx_petitions_bnt_forfeited ON petitions(bnt_forfeited);
