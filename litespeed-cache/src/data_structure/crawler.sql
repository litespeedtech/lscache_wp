  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(1000) NOT NULL DEFAULT '',
  `status` varchar(255) NOT NULL DEFAULT '' COMMENT '-=not crawl, H=hit, M=miss, B=blacklist',
  `reason` text NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `status` (`status`)
