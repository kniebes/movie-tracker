<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Service;

use Kniebes\MovieTracker\Storage\Storage;
use Kniebes\MovieTracker\Storage\Table;

class Auth
{
    private const int TOKEN_LIFETIME = 3600 * 24 * 30;

    public function isAuthenticated(): bool
    {
        $token = $_COOKIE['token'] ?? null;
        if (empty($token)) {
            return false;
        }

        $session = Storage::getInstance()->selectOne(
            sql: 'SELECT id FROM `' . Table::SESSION . '` WHERE `token` = :token AND `active` = 1',
            parameters: ['token' => $token]
        );

        return $session !== null;
    }

    public function login(string $username, string $password): bool
    {
        if (!$this->checkCredentials(username: $username, password: $password)) {
            return false;
        }

        $token = bin2hex(random_bytes(32));
        Storage::getInstance()->execute(
            sql: 'INSERT INTO `' . Table::SESSION . '` SET `token` = :token, `active` = 1, `created` = NOW(), `last_login` = NOW(), `user_agent` = :user_agent',
            parameters: [
                'token' => $token,
                'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 255),
            ]
        );

        setcookie('token', $token, [
            'expires' => time() + self::TOKEN_LIFETIME,
            'path' => '/',
            'secure' => $this->isHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        return true;
    }

    public function logout(): void
    {
        $token = $_COOKIE['token'] ?? null;
        if (!empty($token)) {
            Storage::getInstance()->execute(
                sql: 'UPDATE `' . Table::SESSION . '` SET `active` = 0 WHERE `token` = :token',
                parameters: ['token' => $token]
            );
        }

        setcookie('token', '', [
            'expires' => time() - 3600,
            'path' => '/',
        ]);
    }

    protected function checkCredentials(string $username, string $password): bool
    {
        $configuredUsername = $_ENV['AUTH_USERNAME'] ?? '';
        $configuredPasswordHash = $_ENV['AUTH_PASSWORD_HASH'] ?? '';
        if ($configuredUsername === '' || $configuredPasswordHash === '') {
            return false;
        }

        return hash_equals(known_string: $configuredUsername, user_string: $username)
            && password_verify(password: $password, hash: $configuredPasswordHash);
    }

    protected function isHttps(): bool
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}
