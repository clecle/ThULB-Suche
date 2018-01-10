ALTER TABLE user ADD cat_id varchar(255) AFTER email;
CREATE UNIQUE INDEX cat_id ON user (cat_id);