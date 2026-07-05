<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Bootstrap;

use Symfony\Component\Dotenv\Dotenv;

class Environment
{
    public static function init(string $projectRoot): void
    {
        $envFile = $projectRoot . '/.env';
        if (is_file($envFile)) {
            (new Dotenv())->loadEnv($envFile);
        }

        self::configureErrorLog($projectRoot);
    }

    /**
     * Leitet PHP-Fehler und error_log()-Aufrufe nach <projectRoot>/log/error.log um.
     */
    private static function configureErrorLog(string $projectRoot): void
    {
        $logDirectory = $projectRoot . '/log';
        if (!is_dir($logDirectory)) {
            mkdir(directory: $logDirectory, permissions: 0775);
        }

        ini_set(option: 'error_log', value: $logDirectory . '/error.log');
    }
}
