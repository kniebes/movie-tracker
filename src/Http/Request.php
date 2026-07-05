<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Http;

class Request
{
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

        parse_str(file_get_contents('php://input'), $parsedBody);

        return $parsedBody;
    }
}
