ALTER TABLE petitions ADD COLUMN legacy_term_penalty_today INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN legacy_term_forfeited INTEGER NOT NULL DEFAULT 0;
ALTER TABLE petitions ADD COLUMN legacy_term_penalty_maximum INTEGER NOT NULL DEFAULT 0;

