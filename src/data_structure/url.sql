`id` bigint(20) NOT NULL AUTO_INCREMENT,
`url` varchar(500) NOT NULL,
`cache_tags` varchar(1000) NOT NULL DEFAULT '',
PRIMARY KEY (`id`),
UNIQUE KEY `url` (`url`),
KEY `cache_tags` (`cache_tags`)