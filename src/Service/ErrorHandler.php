<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Service;

use Kniebes\MovieTracker\Http\Response;
use Kniebes\MovieTracker\View\Template;
use PDOException;
use Throwable;

class ErrorHandler
{
    /**
     * Zentrale Anlaufstelle für alle nicht abgefangenen Exceptions:
     * loggt die Details und liefert dem Browser eine generische Fehlerseite,
     * ohne Interna (SQL, Pfade, Stacktrace) preiszugeben.
     */
    public static function handle(Throwable $throwable): never
    {
        error_log('[movie-tracker] ' . $throwable::class . ': ' . $throwable->getMessage()
            . ' in ' . $throwable->getFile() . ':' . $throwable->getLine());

        $message = $throwable instanceof PDOException
            ? 'Die Datenbank ist nicht erreichbar oder die Abfrage ist fehlgeschlagen.'
            : 'Es ist ein unerwarteter Fehler aufgetreten.';

        try {
            $page = Template::render(template: 'error.html.php', variables: [
                'statusCode' => 500,
                'message' => $message . ' Details stehen im Server-Log.',
            ]);
        } catch (Throwable) {
            // Fallback, falls schon das Rendern der Fehlerseite scheitert
            $page = '<h1>Fehler</h1><p>' . escape($message) . '</p>';
        }

        Response::html(content: $page, statusCode: 500);
    }
}
