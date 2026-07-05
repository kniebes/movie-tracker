<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Bootstrap;

use RuntimeException;

class Environment
{
    public static function init(string $projectRoot): void
    {
        $envFile = $projectRoot . '/.env';
        if (!is_file($envFile)) {
            throw new RuntimeException('Keine .env gefunden. Bitte .env.dist nach .env kopieren und ausfüllen.');
        }

        $lines = file(filename: $envFile, flags: FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with(haystack: $line, needle: '#')) {
                continue;
            }

            [$name, $value] = array_pad(array: explode('=', $line, limit: 2), length: 2, value: '');
            $name = trim($name);
            $value = trim(trim($value), characters: '"\'');
            if ($name !== '') {
                $_ENV[$name] = $value;
            }
        }
    }
}
