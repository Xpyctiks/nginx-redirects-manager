CREATE TABLE `domains` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Uniq ID',
  `domain` tinytext NOT NULL COMMENT 'Domain name',
  `path` tinytext NOT NULL COMMENT 'Path to conf file',
  `type` tinytext NOT NULL COMMENT 'Type of redirect',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `templates` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Unid id',
  `name` tinytext NOT NULL,
  `type` tinytext NOT NULL COMMENT 'Type of redirect',
  `template` text NOT NULL COMMENT 'Template for redirect',
  `created` datetime NOT NULL DEFAULT current_timestamp(),
  `hint` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `users` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT COMMENT 'User uniqID',
  `username` tinytext NOT NULL COMMENT 'User name',
  `password` tinytext NOT NULL COMMENT 'User password',
  `realname` tinytext NOT NULL COMMENT 'Real user name',
  `role` smallint(5) unsigned NOT NULL DEFAULT 1 COMMENT 'User role',
  `created` datetime NOT NULL COMMENT 'When created',
  `session` tinytext DEFAULT NULL COMMENT 'Session token',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`(100))
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
