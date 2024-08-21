CREATE TABLE `login_token` (
   `id` int NOT NULL AUTO_INCREMENT,
   `user_id` int NOT NULL,
   `token` varchar(255) NOT NULL,
   `series` varchar(255) NOT NULL,
   `last_login` datetime NOT NULL,
   `browser` varchar(255) NULL,
   `platform` varchar(255) NULL,
   `expires` int NOT NULL,
   `last_session_id` varchar(255) NULL,
   PRIMARY KEY (`id`),
   KEY `user_id_series` (`user_id`, `series`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
