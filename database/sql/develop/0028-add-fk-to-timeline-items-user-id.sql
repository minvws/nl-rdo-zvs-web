
ALTER TABLE timeline_items
ADD CONSTRAINT fk_timeline_items_user_id
FOREIGN KEY (user_id) REFERENCES users(id)
ON DELETE RESTRICT;
