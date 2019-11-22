`id` bigint(20) NOT NULL AUTO_INCREMENT,
`hash_name` varchar(60) NOT NULL COMMENT "hash.filetype",
`src` text NOT NULL COMMENT "full url array set",
`dateline` int(11) NOT NULL,
`refer` varchar(255) NOT NULL COMMENT "The container page url",
PRIMARY KEY (`id`),
UNIQUE KEY `hash_name` (`hash_name`),
KEY `dateline` (`dateline`)
