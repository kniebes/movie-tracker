<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Repository;

use Kniebes\MovieTracker\Storage\Storage;

class MovieRepository
{
    public const array FIELDS = ['title', 'original_title', 'series', 'episode', 'year', 'seen', 'rating', 'url', 'comment', 'type'];

    public function findById(int $id): ?object
    {
        return Storage::getInstance()->selectOne(
            sql: 'SELECT * FROM `movie` WHERE `id` = :id',
            parameters: ['id' => $id]
        );
    }

    /** @return object[] */
    public function search(string $query = '', ?string $type = null, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT * FROM `movie` WHERE 1 = 1';
        $parameters = [];

        if ($query !== '') {
            $sql .= ' AND (`title` LIKE :query OR `original_title` LIKE :query)';
            $parameters['query'] = '%' . $query . '%';
        }
        if ($type !== null) {
            $sql .= ' AND `type` = :type';
            $parameters['type'] = $type;
        }

        $sql .= ' ORDER BY `seen` DESC, `id` DESC LIMIT ' . intval($limit) . ' OFFSET ' . intval($offset);

        return Storage::getInstance()->select(sql: $sql, parameters: $parameters);
    }

    /** @return string[] Titel aller Serien und Episoden für die Titel-Vervollständigung */
    public function seriesTitles(): array
    {
        $rows = Storage::getInstance()->select(
            sql: 'SELECT DISTINCT `title` FROM `movie` WHERE `type` IN ("series", "episode") ORDER BY `title`'
        );

        return array_map(fn (object $row) => $row->title, $rows);
    }

    public function insert(array $data): int
    {
        $parameters = $this->normalizeFields($data);
        $sql = <<<SQL
INSERT INTO `movie`
(`title`, `original_title`, `series`, `episode`, `year`, `seen`, `rating`, `url`, `comment`, `type`)
VALUES
(:title, :original_title, :series, :episode, :year, :seen, :rating, :url, :comment, :type)
SQL;
        Storage::getInstance()->execute(sql: $sql, parameters: $parameters);

        return Storage::getInstance()->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $parameters = $this->normalizeFields($data);
        $parameters['id'] = $id;
        $sql = 'UPDATE `movie` SET `title` = :title, `original_title` = :original_title, `series` = :series, `episode` = :episode, `year` = :year, `seen` = :seen, `rating` = :rating, `url` = :url, `comment` = :comment, `type` = :type WHERE `id` = :id';
        Storage::getInstance()->execute(sql: $sql, parameters: $parameters);
    }

    public function delete(int $id): void
    {
        Storage::getInstance()->execute(
            sql: 'DELETE FROM `movie_cast_relation` WHERE `movie_id` = :id',
            parameters: ['id' => $id]
        );
        Storage::getInstance()->execute(
            sql: 'DELETE FROM `movie` WHERE `id` = :id',
            parameters: ['id' => $id]
        );
    }

    /** @return string[] */
    public function castNames(int $movieId): array
    {
        $sql = <<<SQL
SELECT c.`name` FROM `movie_cast` c
INNER JOIN `movie_cast_relation` r ON r.`movie_cast_id` = c.`id` AND r.`movie_id` = :id
ORDER BY c.`name`
SQL;
        $rows = Storage::getInstance()->select(sql: $sql, parameters: ['id' => $movieId]);

        return array_map(fn (object $row) => $row->name, $rows);
    }

    /** Leere Strings werden zu NULL, damit optionale Spalten nicht mit '' gefüllt werden. */
    protected function normalizeFields(array $data): array
    {
        $parameters = [];
        foreach (self::FIELDS as $field) {
            $value = $data[$field] ?? null;
            $parameters[$field] = ($value === '') ? null : $value;
        }

        // `comment` und `seen` sind NOT NULL
        $parameters['comment'] = $parameters['comment'] ?? '';
        $parameters['seen'] = $parameters['seen'] ?? date('Y-m-d');

        return $parameters;
    }
}
