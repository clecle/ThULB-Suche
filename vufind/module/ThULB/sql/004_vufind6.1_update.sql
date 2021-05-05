/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `auth_hash` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `session_id` varchar(128) DEFAULT NULL,
  `hash` varchar(255) NOT NULL DEFAULT '',
  `type` varchar(50) DEFAULT NULL,
  `data` mediumtext,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `session_id` (`session_id`),
  UNIQUE KEY `hash_type` (`hash`, `type`),
  KEY `created` (`created`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

alter table `user` add `last_language` varchar(30) not null default '';
alter table `user` add `pending_email` varchar(255) not null default '';
alter table `user` add `user_provided_email` tinyint(1) not null default '0';

alter table `search` add `notification_frequency` int(11) not null default '0';
alter table `search` add `last_notification_sent` datetime not null default '2000-01-01 00:00:00';
alter table `search` add `notification_base_url` varchar(255) not null default '';

alter table `search` modify `id` bigint unsigned auto_increment;
alter table `session` modify `id` bigint unsigned auto_increment;
alter table `external_session` modify `id` bigint unsigned auto_increment;
