CREATE TABLE `ratings` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `user_id` int(11) DEFAULT NULL,
   `resource_id` int(11) NOT NULL DEFAULT '0',
   `rating` int(3) NOT NULL,
   `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
   PRIMARY KEY (`id`),
   KEY `user_id` (`user_id`),
   KEY `resource_id` (`resource_id`),
   CONSTRAINT `ratings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
   CONSTRAINT `ratings_ibfk_2` FOREIGN KEY (`resource_id`) REFERENCES `resource` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `feedback` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `message` longtext,
    `form_data` json DEFAULT NULL,
    `form_name` varchar(255) NOT NULL,
    `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_by` int(11) DEFAULT NULL,
    `status` varchar(255) NOT NULL DEFAULT 'open',
    `site_url` varchar(255) NOT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `created` (`created`),
    KEY `status` (`status`(191)),
    KEY `form_name` (`form_name`(191)),
    CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL,
    CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`updated_by`) REFERENCES `user` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE TABLE `access_token` (
    `id` varchar(255) NOT NULL,
    `type` varchar(128) NOT NULL,
    `user_id` int(11) NULL,
    `created` datetime NOT NULL DEFAULT '2000-01-01 00:00:00',
    `data` mediumtext DEFAULT NULL,
    `revoked` tinyint(1) NOT NULL DEFAULT '0',
    PRIMARY KEY (`id`, `type`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `access_token_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `user` MODIFY COLUMN `home_library` varchar(100) DEFAULT '';
ALTER TABLE `user_card` MODIFY COLUMN `home_library` varchar(100) DEFAULT '';
ALTER TABLE `search` ADD KEY `created_saved` (`created`,`saved`);