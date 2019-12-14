  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `url` varchar(1000) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '0' COMMENT 'bitmask 1=miss, 2=hit, 4=blacklisted',
  `reason` text NOT NULL,
  `mtime` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `url` (`url`),
  KEY `status` (`status`)
