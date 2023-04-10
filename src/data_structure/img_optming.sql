  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `post_id` bigint(20) unsigned NOT NULL DEFAULT '0',
  `optm_status` tinyint(4) NOT NULL DEFAULT '0',
  `src` varchar(1000) NOT NULL DEFAULT '',
  `server_info` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `post_id` (`post_id`),
  KEY `optm_status` (`optm_status`),
  KEY `src` (`src`(191))
