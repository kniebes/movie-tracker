<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Http;

use Kniebes\MovieTracker\View\Template;

class Response
{
    public static function html(string $content, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: text/html; charset=utf-8');
        echo $content;
        exit;
    }

    /**
     * Leitet um. Bei htmx-Requests per HX-Redirect-Header, damit htmx einen
     * vollständigen Seitenwechsel macht statt die Zielseite ins Target zu swappen.
     */
    public static function redirect(string $url): never
    {
        if (Request::isHtmx()) {
            header('HX-Redirect: ' . $url);
        } else {
            header('Location: ' . $url, response_code: 302);
        }
        exit;
    }

    public static function notFound(string $message = 'Diese Seite gibt es nicht.'): never
    {
        self::html(
            content: Template::render(template: 'error.html.php', variables: [
                'statusCode' => 404,
                'message' => $message,
            ]),
            statusCode: 404
        );
    }
}
