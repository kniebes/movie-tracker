<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Controller;

use Kniebes\MovieTracker\Http\Request;
use Kniebes\MovieTracker\Http\Response;
use Kniebes\MovieTracker\Repository\CastRepository;
use Kniebes\MovieTracker\View\Template;
use PDOException;

class CastController
{
    public function __construct(
        private readonly CastRepository $castRepository = new CastRepository(),
    ) {
    }

    public function list(): never
    {
        Response::html(Template::page(
            template: 'cast/list.html.php',
            variables: ['castMembers' => $this->castRepository->listWithMovies()],
            title: 'Darsteller',
            activeNavigation: 'cast'
        ));
    }

    public function row(int $id): never
    {
        Response::html($this->renderRow($id));
    }

    public function editRow(int $id): never
    {
        $castMember = $this->findOrFail($id);
        Response::html(Template::render(template: 'cast/_row-edit.html.php', variables: ['castMember' => $castMember]));
    }

    public function update(int $id): never
    {
        $this->findOrFail($id);

        $body = Request::body();
        $name = trim($body['name'] ?? '');
        $url = trim($body['url'] ?? '');

        if ($name === '') {
            $this->renderEditRowWithError(id: $id, name: $name, url: $url, error: 'Der Name darf nicht leer sein.');
        }

        try {
            $this->castRepository->update(id: $id, name: $name, url: $url);
        } catch (PDOException $exception) {
            error_log('[movie-tracker] Update von Darsteller ' . $id . ' fehlgeschlagen: ' . $exception->getMessage());
            $this->renderEditRowWithError(id: $id, name: $name, url: $url, error: 'Speichern fehlgeschlagen. Bitte die Werte prüfen (Name und URL maximal 255 Zeichen).');
        }

        Response::html($this->renderRow($id));
    }

    /** Zeigt die Inline-Bearbeitung erneut, mit den eingegebenen Werten und einer Fehlermeldung. */
    protected function renderEditRowWithError(int $id, string $name, string $url, string $error): never
    {
        $castMember = new \stdClass();
        $castMember->id = $id;
        $castMember->name = $name;
        $castMember->url = $url;

        Response::html(
            content: Template::render(template: 'cast/_row-edit.html.php', variables: [
                'castMember' => $castMember,
                'error' => $error,
            ]),
            statusCode: 422
        );
    }

    public function delete(int $id): never
    {
        $this->castRepository->delete($id);

        // Leere Antwort: htmx entfernt die Zeile per hx-swap="outerHTML".
        Response::html('');
    }

    protected function renderRow(int $id): string
    {
        $castMember = $this->castRepository->findWithMovies($id);
        if ($castMember === null) {
            Response::notFound('Darsteller nicht gefunden');
        }

        return Template::render(template: 'cast/_row.html.php', variables: ['castMember' => $castMember]);
    }

    protected function findOrFail(int $id): object
    {
        $castMember = $this->castRepository->findById($id);
        if ($castMember === null) {
            Response::notFound('Darsteller nicht gefunden');
        }

        return $castMember;
    }
}
