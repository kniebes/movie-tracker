<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Storage;

use PDO;

class Storage
{
    private static ?Storage $instance = null;
    private PDO $connection;

    private function __construct()
    {
        $this->connection = new PDO(
            dsn: 'mysql:host=' . $_ENV['STORAGE_HOST'] . ';dbname=' . $_ENV['STORAGE_DB'] . ';charset=utf8mb4',
            username: $_ENV['STORAGE_USERNAME'],
            password: $_ENV['STORAGE_PASSWORD'],
            options: [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            ]
        );
    }

    public static function getInstance(): Storage
    {
        if (self::$instance === null) {
            self::$instance = new Storage();
        }

        return self::$instance;
    }

    /** @return object[] */
    public function select(string $sql, array $parameters = []): array
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function selectOne(string $sql, array $parameters = []): ?object
    {
        $rows = $this->select(sql: $sql, parameters: $parameters);

        return $rows[0] ?? null;
    }

    public function execute(string $sql, array $parameters = []): int
    {
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $statement->rowCount();
    }

    public function getLastInsertId(): int
    {
        return intval($this->connection->lastInsertId());
    }
}
