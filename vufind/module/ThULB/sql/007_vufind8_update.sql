ALTER TABLE `auth_hash` DROP KEY `hash_type`;
ALTER TABLE `auth_hash` ADD UNIQUE KEY `hash_type` (`hash`(140),`type`);
ALTER TABLE `auth_hash` MODIFY `data` mediumtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `auth_hash` MODIFY `hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `auth_hash` MODIFY `session_id` varchar(128) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `auth_hash` MODIFY `type` varchar(50) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `auth_hash` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `change_tracker` MODIFY `id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `change_tracker` MODIFY `core` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `change_tracker` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `comments` MODIFY `comment` text COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `comments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `external_session` DROP KEY `external_session_id`;
ALTER TABLE `external_session` ADD KEY `external_session_id` (`external_session_id`(190));
ALTER TABLE `external_session` MODIFY `external_session_id` varchar(255) COLLATE utf8mb4_bin NOT NULL;
ALTER TABLE `external_session` MODIFY `session_id` varchar(128) COLLATE utf8mb4_bin NOT NULL;
ALTER TABLE `external_session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;

ALTER TABLE `oai_resumption` MODIFY `params` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `oai_resumption` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `record` DROP KEY `record_id_source`;
ALTER TABLE `record` ADD UNIQUE KEY `record_id_source` (`record_id`(140),`source`);
ALTER TABLE `record` MODIFY `data` longtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `record` MODIFY `record_id` varchar(255) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `record` MODIFY `source` varchar(50) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `record` MODIFY `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `record` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `resource` DROP KEY `record_id`;
ALTER TABLE `resource` ADD KEY `record_id` (`record_id`(190));
ALTER TABLE `resource` MODIFY `author` varchar(255) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `resource` MODIFY `extra_metadata` mediumtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `resource` MODIFY `record_id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `resource` MODIFY `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Solr';
ALTER TABLE `resource` MODIFY `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `resource` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `resource_tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `search` ADD KEY `notification_frequency` (`notification_frequency`);
ALTER TABLE `search` ADD KEY `notification_base_url` (`notification_base_url`(190));
ALTER TABLE `search` MODIFY `notification_base_url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `search` MODIFY `session_id` varchar(128) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `search` MODIFY `title` varchar(20) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `search` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `session` MODIFY `data` mediumtext COLLATE utf8mb4_unicode_ci;
ALTER TABLE `session` MODIFY `session_id` varchar(128) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `session` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `shortlinks` MODIFY `hash` varchar(32) COLLATE utf8mb4_bin;
ALTER TABLE `shortlinks` MODIFY `path` mediumtext COLLATE utf8mb4_bin NOT NULL;
ALTER TABLE `shortlinks` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;

ALTER TABLE `tags` MODIFY `tag` varchar(64) COLLATE utf8mb4_bin NOT NULL DEFAULT '';
ALTER TABLE `tags` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;

ALTER TABLE `user` DROP KEY `username`;
ALTER TABLE `user` DROP KEY `cat_id`;
ALTER TABLE `user` ADD UNIQUE KEY `username` (`username`(190));
ALTER TABLE `user` ADD UNIQUE KEY `cat_id` (`cat_id`(190));
ALTER TABLE `user` MODIFY `auth_method` varchar(50) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` MODIFY `cat_id` varchar(255) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` MODIFY `cat_password` varchar(70) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` MODIFY `cat_pass_enc` varchar(255) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` MODIFY `cat_username` varchar(50) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` MODIFY `college` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `firstname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `home_library` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `lastname` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `last_language` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `major` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `pass_hash` varchar(60) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user` MODIFY `password` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `pending_email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `username` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` MODIFY `verify_hash` varchar(42) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_card` MODIFY `card_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user_card` MODIFY `cat_username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user_card` MODIFY `cat_pass_enc` varchar(255) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_card` MODIFY `cat_password` varchar(70) COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_card` MODIFY `home_library` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '';
ALTER TABLE `user_card` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_list` MODIFY `description` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_list` MODIFY `title` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL;
ALTER TABLE `user_list` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user_resource` MODIFY `notes` text COLLATE utf8mb4_unicode_ci;
ALTER TABLE `user_resource` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
