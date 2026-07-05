CREATE TABLE `movie_cast_relation` (
  `movie_id` int(11) NOT NULL DEFAULT 0,
  `movie_cast_id` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`movie_id`,`movie_cast_id`),
  KEY `movie_cast_relation_ibfk_2` (`movie_cast_id`),
  CONSTRAINT `movie_cast_relation_ibfk_1` FOREIGN KEY (`movie_id`) REFERENCES `movie` (`id`),
  CONSTRAINT `movie_cast_relation_ibfk_2` FOREIGN KEY (`movie_cast_id`) REFERENCES `movie_cast` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
