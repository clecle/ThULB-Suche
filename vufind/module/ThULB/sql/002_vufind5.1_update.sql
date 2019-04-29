ALTER TABLE `comments` MODIFY `user_id` INT DEFAULT NULL null;
ALTER TABLE `comments` DROP FOREIGN KEY comments_ibfk_1;
ALTER TABLE `comments` ADD CONSTRAINT comments_ibfk_1 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL;

ALTER TABLE `resource` ADD `extra_metadata` MEDIUMTEXT DEFAULT NULL NULL;

ALTER TABLE `session` CHANGE `data` `data` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL;

ALTER TABLE `user` MODIFY `cat_password` VARCHAR(70) DEFAULT NULL NULL;
ALTER TABLE `user` MODIFY `cat_pass_enc` VARCHAR(255) DEFAULT NULL NULL;
ALTER TABLE `user` ADD `last_login` DATETIME NOT NULL DEFAULT '2000-01-01 00:00:00' AFTER `verify_hash`;
ALTER TABLE `user` ADD `auth_method` VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `last_login`;

ALTER TABLE `user_card` MODIFY `cat_password` VARCHAR(70) DEFAULT NULL NULL;
ALTER TABLE `user_card` MODIFY `cat_pass_enc` VARCHAR(255) DEFAULT NULL NULL;