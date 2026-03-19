ALTER TABLE user_global_roles
ALTER COLUMN id SET DATA TYPE UUID USING (id::uuid),
ALTER COLUMN user_id SET DATA TYPE UUID USING (user_id::uuid);

ALTER TABLE user_global_roles
ADD CONSTRAINT fk_user_global_roles_user_id
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE RESTRICT;
