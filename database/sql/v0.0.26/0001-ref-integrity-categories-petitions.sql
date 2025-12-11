ALTER TABLE "petitions"
    ADD CONSTRAINT fk_petitions_petition_category_id
    FOREIGN KEY (petition_category_id)
    REFERENCES petition_categories(id)
    ON DELETE SET NULL;