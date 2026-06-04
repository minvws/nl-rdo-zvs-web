ALTER TABLE decisions 
ADD COLUMN team_id uuid DEFAULT NULL;

ALTER TABLE decisions
    ADD CONSTRAINT "fk_decisions_team_id"
    FOREIGN KEY ("team_id") REFERENCES "teams" ("id") ON DELETE SET NULL;

CREATE INDEX idx_decisions_team_id ON decisions(team_id);
