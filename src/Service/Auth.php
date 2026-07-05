<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Service;

use Kniebes\MovieTracker\Storage\Storage;
use Kniebes\MovieTracker\Storage\Table;

class Auth
{
    /** Gilt für das Cookie und die serverseitige Gültigkeit des Tokens. */
    private const int TOKEN_LIFETIME = 3600 * 24 * 30;

    /** Nach so vielen Fehlversuchen innerhalb des Zeitfensters ist der Login gesperrt. */
    private const int MAX_LOGIN_ATTEMPTS = 5;
    private const int LOGIN_ATTEMPT_WINDOW = 60 * 15;

    public function isAuthenticated(): bool
    {
        $token = $_COOKIE['token'] ?? null;
        if (empty($token)) {
            return false;
        }

        $session = Storage::getInstance()->selectOne(
            sql: 'SELECT id FROM `' . Table::SESSION . '` WHERE `token` = :token AND `active` = 1'
                . ' AND `created` > NOW() - INTERVAL ' . self::TOKEN_LIFETIME . ' SECOND',
            parameters: ['token' => $token]
        );

        return $session !== null;
    }

    public function login(string $username, string $password): bool
    {
        if ($this->isThrottled()) {
            return false;
        }

        if (!$this->checkCredentials(username: $username, password: $password)) {
            $this->recordFailedAttempt();

            return false;
        }

        $this->clearFailedAttempts();
        $this->purgeExpiredSessions();

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

    /**
     * Brute-Force-Schutz: gesperrt, sobald zu viele Fehlversuche im Zeitfenster liegen.
     * Bewusst global statt pro IP, weil es nur einen Benutzer gibt.
     */
    public function isThrottled(): bool
    {
        $row = Storage::getInstance()->selectOne(
            sql: 'SELECT COUNT(*) AS failures FROM `' . Table::LOGIN_ATTEMPT . '`'
                . ' WHERE `attempted_at` > NOW() - INTERVAL ' . self::LOGIN_ATTEMPT_WINDOW . ' SECOND'
        );

        return intval($row?->failures) >= self::MAX_LOGIN_ATTEMPTS;
    }

    protected function recordFailedAttempt(): void
    {
        $storage = Storage::getInstance();

        // Einträge außerhalb des Zeitfensters gleich mit wegräumen
        $storage->execute(
            sql: 'DELETE FROM `' . Table::LOGIN_ATTEMPT . '`'
                . ' WHERE `attempted_at` <= NOW() - INTERVAL ' . self::LOGIN_ATTEMPT_WINDOW . ' SECOND'
        );
        // IP nur als gepepperter Hash: Muster bleiben erkennbar (gleiche IP = gleicher
        // Wert), aber die Adresse steht nicht im Klartext in der Datenbank. Ohne Pepper
        // wäre ein IPv4-Hash per Enumeration trivial rückrechenbar.
        $ipHash = hash(
            algo: 'sha256',
            data: ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . ($_ENV['AUTH_PASSWORD_HASH'] ?? '')
        );
        $storage->execute(
            sql: 'INSERT INTO `' . Table::LOGIN_ATTEMPT . '` SET `ip_hash` = :ip_hash',
            parameters: ['ip_hash' => $ipHash]
        );
    }

    protected function clearFailedAttempts(): void
    {
        Storage::getInstance()->execute('DELETE FROM `' . Table::LOGIN_ATTEMPT . '`');
    }

    /**
     * Räumt abgelaufene und deaktivierte Sessions weg, damit die Tabelle nicht
     * unbegrenzt wächst. Läuft beim Login, weil das selten genug ist.
     */
    protected function purgeExpiredSessions(): void
    {
        Storage::getInstance()->execute(
            sql: 'DELETE FROM `' . Table::SESSION . '` WHERE `active` = 0'
                . ' OR `created` <= NOW() - INTERVAL ' . self::TOKEN_LIFETIME . ' SECOND'
        );
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
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        // Hinter einem Reverse-Proxy (TLS-Terminierung) meldet erst dieser Header HTTPS.
        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }
}
