CREATE TABLE `userpanel_oauth_apps` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`name` varchar(100) CHARACTER SET utf8 NOT NULL,
	`token` varchar(32) CHARACTER SET latin1 COLLATE latin1_general_cs NOT NULL,
	`secret` varchar(100) NOT NULL,
	`user_id` int(11) NOT NULL,
	`logo` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
	`ip` varchar(15) DEFAULT NULL,
	`status` tinyint(4) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `token` (`token`),
	KEY `user` (`user_id`),
	CONSTRAINT `userpanel_oauth_apps_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userpanel_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `userpanel_oauth_accesses` (
	`id` int(11) NOT NULL AUTO_INCREMENT,
	`user_id` int(11) NOT NULL,
	`app_id` int(11) NOT NULL,
	`code` varchar(32) NOT NULL,
	`token` varchar(32) NOT NULL,
	`create_at` int(11) NOT NULL,
	`lastip` varchar(15) DEFAULT NULL,
	`lastuse_at` int(11) DEFAULT NULL,
	`expire_token_at` int(11) DEFAULT NULL,
	`status` tinyint(1) NOT NULL,
	PRIMARY KEY (`id`),
	UNIQUE KEY `code` (`code`),
	UNIQUE KEY `token` (`token`),
	KEY `user` (`user_id`),
	KEY `app` (`app_id`),
	CONSTRAINT `userpanel_oauth_accesses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `userpanel_users` (`id`) ON DELETE CASCADE,
	CONSTRAINT `userpanel_oauth_accesses_ibfk_2` FOREIGN KEY (`app_id`) REFERENCES `userpanel_oauth_apps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `options` (`name`, `value`, `autoload`) VALUES ('packages.userpanel_oauth.accesses.token_lifetime', '3600', '1'); 