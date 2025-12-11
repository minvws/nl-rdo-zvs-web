CREATE TABLE "petition_petition"
(
    "petition_id" uuid NOT NULL,
    "related_petition_id" uuid NOT NULL

);

ALTER TABLE
    "petition_petition"
    owner TO "cts";
