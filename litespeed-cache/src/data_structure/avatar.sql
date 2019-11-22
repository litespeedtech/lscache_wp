  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(1000) NOT NULL DEFAULT '',
  `md5` varchar(128) NOT NULL DEFAULT '',
  `dateline` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `md5` (`md5`),
  KEY `dateline` (`dateline`)
