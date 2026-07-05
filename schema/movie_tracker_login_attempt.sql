CREATE TABLE `movie_tracker_login_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_hash` char(64) DEFAULT NULL,
  `attempted_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_attempted_at` (`attempted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
