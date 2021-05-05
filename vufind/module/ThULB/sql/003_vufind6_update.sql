ALTER TABLE `user` ADD `email_verified` DATETIME DEFAULT NULL;

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shortlinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `path` mediumtext NOT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE utf8_bin;
/*!40101 SET character_set_client = @saved_cs_client */;
