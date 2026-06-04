ALTER TABLE petitions 
ADD COLUMN team_id uuid DEFAULT NULL;

ALTER TABLE petitions
    ADD CONSTRAINT "fk_petitions_team_id"
    FOREIGN KEY ("team_id") REFERENCES "teams" ("id") ON DELETE SET NULL;

CREATE INDEX idx_petitions_team_id ON petitions(team_id);
