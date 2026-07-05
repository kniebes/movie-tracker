<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Repository;

use Kniebes\MovieTracker\Storage\Storage;
use Kniebes\MovieTracker\Storage\Table;

class MovieRepository
{
    public const array FIELDS = ['title', 'original_title', 'series', 'episode', 'year', 'seen', 'rating', 'url', 'comment', 'type'];

    public function findById(int $id): ?object
    {
        return Storage::getInstance()->selectOne(
            sql: 'SELECT * FROM `' . Table::MOVIE . '` WHERE `id` = :id',
            parameters: ['id' => $id]
        );
    }

    /** @return object[] */
    public function search(string $query = '', ?string $type = null, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT * FROM `' . Table::MOVIE . '` WHERE 1 = 1';
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
            sql: 'SELECT DISTINCT `title` FROM `' . Table::MOVIE . '` WHERE `type` IN ("series", "episode") ORDER BY `title`'
        );

        return array_map(fn (object $row) => $row->title, $rows);
    }

    public function insert(array $data): int
    {
        $parameters = $this->normalizeFields($data);
        $sql = sprintf(<<<'SQL'
INSERT INTO `%s`
(`title`, `original_title`, `series`, `episode`, `year`, `seen`, `rating`, `url`, `comment`, `type`)
VALUES
(:title, :original_title, :series, :episode, :year, :seen, :rating, :url, :comment, :type)
SQL, Table::MOVIE);
        Storage::getInstance()->execute(sql: $sql, parameters: $parameters);

        return Storage::getInstance()->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $parameters = $this->normalizeFields($data);
        $parameters['id'] = $id;
        $sql = 'UPDATE `' . Table::MOVIE . '` SET `title` = :title, `original_title` = :original_title, `series` = :series, `episode` = :episode, `year` = :year, `seen` = :seen, `rating` = :rating, `url` = :url, `comment` = :comment, `type` = :type WHERE `id` = :id';
        Storage::getInstance()->execute(sql: $sql, parameters: $parameters);
    }

    public function delete(int $id): void
    {
        Storage::getInstance()->transactional(function () use ($id): void {
            Storage::getInstance()->execute(
                sql: 'DELETE FROM `' . Table::MOVIE_CAST_RELATION . '` WHERE `movie_id` = :id',
                parameters: ['id' => $id]
            );
            Storage::getInstance()->execute(
                sql: 'DELETE FROM `' . Table::MOVIE . '` WHERE `id` = :id',
                parameters: ['id' => $id]
            );
        });
    }

    /** @return string[] */
    public function castNames(int $movieId): array
    {
        $sql = sprintf(<<<'SQL'
SELECT c.`name` FROM `%s` c
INNER JOIN `%s` r ON r.`movie_cast_id` = c.`id` AND r.`movie_id` = :id
ORDER BY c.`name`
SQL, Table::MOVIE_CAST, Table::MOVIE_CAST_RELATION);
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
