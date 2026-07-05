CREATE TABLE `movie` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(8) DEFAULT NULL,
  `type` enum('movie','series','episode') NOT NULL DEFAULT 'movie',
  `title` varchar(256) NOT NULL DEFAULT '',
  `original_title` varchar(255) DEFAULT NULL,
  `series` tinyint(3) unsigned DEFAULT NULL,
  `episode` tinyint(3) unsigned DEFAULT NULL,
  `year` decimal(4,0) DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `comment` longtext NOT NULL,
  `comment_encoded` longtext DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `seen` date NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

