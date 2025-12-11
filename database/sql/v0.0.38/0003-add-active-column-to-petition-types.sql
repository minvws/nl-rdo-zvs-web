ALTER TABLE petition_types
ADD COLUMN active BOOLEAN NOT NULL DEFAULT true;

COMMENT ON COLUMN petition_types.active IS 'Indicates if the petition type is active and should be displayed as create button';
