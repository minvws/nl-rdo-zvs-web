CREATE TABLE telescope_entries (
      sequence BIGSERIAL PRIMARY KEY,
      uuid UUID NOT NULL,
      batch_id UUID NOT NULL,
      family_hash VARCHAR(255) NULL,
      should_display_on_index BOOLEAN DEFAULT TRUE NOT NULL,
      type VARCHAR(20) NOT NULL,
      content TEXT NOT NULL,
      created_at TIMESTAMP NULL,
    CONSTRAINT unique_telescope_entries_uuid UNIQUE (uuid)
);
CREATE INDEX index_telescope_entries_batch_id ON telescope_entries (batch_id);
CREATE INDEX index_telescope_entries_family_hash ON telescope_entries (family_hash);
CREATE INDEX index_telescope_entries_created_at ON telescope_entries (created_at);
CREATE INDEX index_telescope_entries_type_should_display_on_index ON telescope_entries (type, should_display_on_index);

CREATE TABLE telescope_entries_tags (
     entry_uuid UUID NOT NULL,
     tag VARCHAR(255) NOT NULL,
         CONSTRAINT telescope_entries_tags_pkey PRIMARY KEY (entry_uuid, tag),
         CONSTRAINT telescope_entries_tags_entry_uuid_foreign FOREIGN KEY (entry_uuid)
             REFERENCES telescope_entries (uuid)
             ON DELETE CASCADE
);

CREATE INDEX index_telescope_entries_tags_tag ON telescope_entries_tags (tag);

CREATE TABLE telescope_monitoring (
     tag VARCHAR(255) PRIMARY KEY
);

ALTER TABLE telescope_entries
    OWNER TO "cts";
ALTER TABLE telescope_entries_tags
    OWNER TO "cts";
ALTER TABLE telescope_monitoring
    OWNER TO "cts";