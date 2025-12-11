ALTER TABLE contacts ALTER COLUMN city TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN email_address TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN email_address_2 TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN email_address_3 TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN house_number TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN initials TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN last_name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN notes TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN organisation_name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN phone_number TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN postal_address_city TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN postal_address_house_number TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN postal_address_postal_code TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN postal_address_street TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN postal_code TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN street TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN visiting_address_city TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN visiting_address_house_number TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN visiting_address_postal_code TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE contacts ALTER COLUMN visiting_address_street TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE custom_costs ALTER COLUMN custom_cost_type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE custom_petition_properties ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE custom_petition_properties ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE decisions ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE decisions ALTER COLUMN reference TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE department_term_type_settings ALTER COLUMN default_value TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE department_term_type_settings ALTER COLUMN field TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE department_term_type_settings ALTER COLUMN term_type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE department_user ALTER COLUMN role TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE departments ALTER COLUMN abbreviation TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE departments ALTER COLUMN hide_column_defaults TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE departments ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE departments ALTER COLUMN slug TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petition_categories ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";


ALTER TABLE petition_deliverables ALTER COLUMN description TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_deliverables ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petition_exports ALTER COLUMN disk TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_exports ALTER COLUMN path TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_exports ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petition_statuses ALTER COLUMN bg_color TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_statuses ALTER COLUMN status TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_statuses ALTER COLUMN status_group TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petition_terms ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petition_type_custom_dates_labels ALTER COLUMN date_label TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petition_types ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_types ALTER COLUMN particularity_label TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petition_types ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE petitions ALTER COLUMN description TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petitions ALTER COLUMN message TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petitions ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE petitions ALTER COLUMN number TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE policy_departments ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE processing_steps ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE processing_steps ALTER COLUMN status TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE public_holidays ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE timeline_items ALTER COLUMN timelineable_type TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE timeline_items ALTER COLUMN type TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE user_global_roles ALTER COLUMN id TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE user_global_roles ALTER COLUMN role TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE user_global_roles ALTER COLUMN user_id TYPE varchar COLLATE "en_US.utf8";

ALTER TABLE users ALTER COLUMN email TYPE varchar COLLATE "en_US.utf8";
ALTER TABLE users ALTER COLUMN name TYPE varchar COLLATE "en_US.utf8";