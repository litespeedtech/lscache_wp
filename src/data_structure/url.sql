`id` bigint(20) NOT NULL AUTO_INCREMENT,
`url` varchar(500) NOT NULL,
`cache_tags` varchar(1000) NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
UNIQUE KEY `url` (`url`(191)),
KEY `cache_tags` (`cache_tags`(191))