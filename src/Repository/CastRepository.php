<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Repository;

use Kniebes\MovieTracker\Storage\Storage;
use Kniebes\MovieTracker\Storage\Table;

class CastRepository
{
    public function findById(int $id): ?object
    {
        return Storage::getInstance()->selectOne(
            sql: 'SELECT * FROM `' . Table::MOVIE_CAST . '` WHERE `id` = :id',
            parameters: ['id' => $id]
        );
    }

    /** @return object[] Darsteller mit kommaseparierter Filmliste und Anzahl */
    public function listWithMovies(): array
    {
        return Storage::getInstance()->select($this->withMoviesSql());
    }

    public function findWithMovies(int $id): ?object
    {
        return Storage::getInstance()->selectOne(
            sql: $this->withMoviesSql(whereClause: 'WHERE c.`id` = :id'),
            parameters: ['id' => $id]
        );
    }

    protected function withMoviesSql(string $whereClause = ''): string
    {
        $sql = sprintf(<<<'SQL'
SELECT
    c.*,
    GROUP_CONCAT(DISTINCT m.`title` ORDER BY m.`seen` DESC SEPARATOR ', ') AS movies,
    COUNT(m.`id`) AS movieCount
FROM `%s` c
LEFT JOIN `%s` r ON r.`movie_cast_id` = c.`id`
LEFT JOIN `%s` m ON m.`id` = r.`movie_id`
SQL, Table::MOVIE_CAST, Table::MOVIE_CAST_RELATION, Table::MOVIE);

        return $sql . ' ' . $whereClause . ' GROUP BY c.`id` ORDER BY c.`name`';
    }

    public function update(int $id, string $name, ?string $url): void
    {
        Storage::getInstance()->execute(
            sql: 'UPDATE `' . Table::MOVIE_CAST . '` SET `name` = :name, `url` = :url WHERE `id` = :id',
            parameters: [
                'id' => $id,
                'name' => $name,
                'url' => ($url === '') ? null : $url,
            ]
        );
    }

    public function delete(int $id): void
    {
        Storage::getInstance()->transactional(function () use ($id): void {
            Storage::getInstance()->execute(
                sql: 'DELETE FROM `' . Table::MOVIE_CAST_RELATION . '` WHERE `movie_cast_id` = :id',
                parameters: ['id' => $id]
            );
            Storage::getInstance()->execute(
                sql: 'DELETE FROM `' . Table::MOVIE_CAST . '` WHERE `id` = :id',
                parameters: ['id' => $id]
            );
        });
    }

    /**
     * Setzt die Darsteller eines Films neu: bestehende Relationen werden ersetzt,
     * unbekannte Namen als neue Darsteller angelegt.
     *
     * @param string[] $names
     */
    public function syncForMovie(int $movieId, array $names): void
    {
        $storage = Storage::getInstance();
        $storage->execute(
            sql: 'DELETE FROM `' . Table::MOVIE_CAST_RELATION . '` WHERE `movie_id` = :id',
            parameters: ['id' => $movieId]
        );

        $castIdList = [];
        foreach ($names as $name) {
            $name = trim($name);
            if ($name === '') {
                continue;
            }

            $existing = $storage->selectOne(
                sql: 'SELECT `id` FROM `' . Table::MOVIE_CAST . '` WHERE `name` = :name',
                parameters: ['name' => $name]
            );

            if ($existing !== null) {
                $castIdList[] = intval($existing->id);
            } else {
                $storage->execute(
                    sql: 'INSERT INTO `' . Table::MOVIE_CAST . '` SET `name` = :name',
                    parameters: ['name' => $name]
                );
                $castIdList[] = $storage->getLastInsertId();
            }
        }

        foreach (array_unique($castIdList) as $castId) {
            $storage->execute(
                sql: 'INSERT INTO `' . Table::MOVIE_CAST_RELATION . '` SET `movie_id` = :movie_id, `movie_cast_id` = :cast_id',
                parameters: [
                    'movie_id' => $movieId,
                    'cast_id' => $castId,
                ]
            );
        }
    }
}
