CREATE TABLE "petition_tag" (
    "petition_id" uuid NOT NULL,
    "tag_id" uuid NOT NULL,
    PRIMARY KEY ("petition_id", "tag_id")
);

ALTER TABLE "petition_tag" OWNER TO "cts";

ALTER TABLE "petition_tag"
    ADD CONSTRAINT fk_petition_tag_petition_id
    FOREIGN KEY (petition_id) REFERENCES petitions (id)
    ON DELETE CASCADE;

ALTER TABLE "petition_tag"
    ADD CONSTRAINT fk_petition_tag_tag_id
    FOREIGN KEY (tag_id) REFERENCES tags (id)
    ON DELETE RESTRICT;

CREATE INDEX "petition_tag_tag_id_index" ON "petition_tag" (tag_id);
