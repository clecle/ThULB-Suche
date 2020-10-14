ALTER TABLE `shortlinks` ADD `hash` varchar(32);
ALTER TABLE `shortlinks` ADD UNIQUE KEY `shortlinks_hash_IDX` USING HASH (`hash`);
