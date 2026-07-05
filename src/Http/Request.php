<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Http;

class Request
{
    private static ?array $parsedBody = null;

    public static function isHtmx(): bool
    {
        return ($_SERVER['HTTP_HX_REQUEST'] ?? '') === 'true';
    }

    /**
     * Liefert den Request-Body als Array. Für PUT/DELETE füllt PHP $_POST nicht,
     * daher wird der Body dort selbst geparst.
     */
    public static function body(): array
    {
        if (!empty($_POST)) {
            return $_POST;
        }

        if (self::$parsedBody === null) {
            parse_str(file_get_contents('php://input'), $parsedBody);
            self::$parsedBody = $parsedBody;
        }

        return self::$parsedBody;
    }

    /**
     * Query-Parameter als String. Manipulierte Array-Parameter (?name[]=…)
     * fallen auf den Default zurück, statt später einen TypeError auszulösen.
     */
    public static function queryString(string $name, string $default = ''): string
    {
        $value = $_GET[$name] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /** Body-Feld als String, analog zu queryString(). */
    public static function bodyString(string $name, string $default = ''): string
    {
        $value = self::body()[$name] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * Body-Feld als Array, reduziert auf String-Werte. Formularfelder sind
     * immer Strings; untergeschobene Sub-Arrays fliegen raus.
     */
    public static function bodyArray(string $name): array
    {
        $value = self::body()[$name] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_filter($value, is_string(...));
    }
}
