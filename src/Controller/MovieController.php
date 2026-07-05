<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Controller;

use Kniebes\MovieTracker\Enum\MovieType;
use Kniebes\MovieTracker\Http\Request;
use Kniebes\MovieTracker\Http\Response;
use Kniebes\MovieTracker\Repository\CastRepository;
use Kniebes\MovieTracker\Repository\MovieRepository;
use Kniebes\MovieTracker\Service\TmdbClient;
use Kniebes\MovieTracker\Storage\Storage;
use Kniebes\MovieTracker\View\Template;
use PDOException;
use Throwable;

class MovieController
{
    private const int PAGE_SIZE = 100;

    public function __construct(
        private readonly MovieRepository $movieRepository = new MovieRepository(),
        private readonly CastRepository $castRepository = new CastRepository(),
    ) {
    }

    public function list(): never
    {
        $query = trim($_GET['q'] ?? '');
        $type = MovieType::tryFrom($_GET['type'] ?? '')?->value;
        $offset = max(0, intval($_GET['offset'] ?? 0));
        $previousMonthKey = $_GET['after'] ?? '';

        // Ein Datensatz mehr als die Seitengröße verrät, ob es weitere gibt.
        $movies = $this->movieRepository->search(
            query: $query,
            type: $type,
            limit: self::PAGE_SIZE + 1,
            offset: $offset
        );
        $hasMore = count($movies) > self::PAGE_SIZE;
        $movies = array_slice($movies, offset: 0, length: self::PAGE_SIZE);

        $rows = Template::render(template: 'movies/_rows.html.php', variables: [
            'movies' => $movies,
            'hasMore' => $hasMore,
            'query' => $query,
            'type' => $type,
            'nextOffset' => $offset + self::PAGE_SIZE,
            'previousMonthKey' => $previousMonthKey,
            'isFirstPage' => $offset === 0,
        ]);

        if (Request::isHtmx()) {
            Response::html($rows);
        }

        Response::html(Template::page(
            template: 'movies/list.html.php',
            variables: [
                'rows' => $rows,
                'query' => $query,
                'type' => $type,
            ],
            title: 'Filme',
            activeNavigation: 'movies'
        ));
    }

    public function createForm(): never
    {
        $this->renderForm(movie: null);
    }

    public function store(): never
    {
        $data = $_POST['data'] ?? [];

        try {
            // Transaktion: scheitert der Cast-Sync, wird auch der Film nicht angelegt.
            $movieId = Storage::getInstance()->transactional(function () use ($data): int {
                $movieId = $this->movieRepository->insert($data);
                $this->castRepository->syncForMovie(movieId: $movieId, names: $this->splitCastNames($data['cast'] ?? ''));

                return $movieId;
            });
        } catch (PDOException $exception) {
            error_log('[movie-tracker] Insert fehlgeschlagen: ' . $exception->getMessage());
            $this->renderForm(
                movie: $this->movieFromInput($data),
                errorMessage: 'Speichern fehlgeschlagen. Bitte die Feldwerte prüfen (z. B. Datum und Jahr).',
                partial: Request::isHtmx()
            );
        }

        Response::redirect('/movies/' . $movieId . '/edit');
    }

    public function editForm(int $id): never
    {
        $movie = $this->movieRepository->findById($id);
        if ($movie === null) {
            Response::notFound('Film nicht gefunden');
        }

        $this->renderForm(movie: $movie);
    }

    public function update(int $id): never
    {
        $movie = $this->movieRepository->findById($id);
        if ($movie === null) {
            Response::notFound('Film nicht gefunden');
        }

        $data = $_POST['data'] ?? [];

        try {
            // Transaktion: scheitert der Cast-Sync, bleiben die bestehenden Zuordnungen erhalten.
            Storage::getInstance()->transactional(function () use ($id, $data): void {
                $this->movieRepository->update(id: $id, data: $data);
                $this->castRepository->syncForMovie(movieId: $id, names: $this->splitCastNames($data['cast'] ?? ''));
            });
        } catch (PDOException $exception) {
            error_log('[movie-tracker] Update von Film ' . $id . ' fehlgeschlagen: ' . $exception->getMessage());
            $this->renderForm(
                movie: $this->movieFromInput($data, id: $id),
                errorMessage: 'Speichern fehlgeschlagen. Bitte die Feldwerte prüfen (z. B. Datum und Jahr).',
                partial: Request::isHtmx()
            );
        }

        if (!Request::isHtmx()) {
            Response::redirect('/movies/' . $id . '/edit');
        }

        $this->renderForm(movie: $this->movieRepository->findById($id), savedMessage: 'Gespeichert', partial: true);
    }

    public function delete(int $id): never
    {
        $this->movieRepository->delete($id);
        Response::redirect('/movies');
    }

    public function tmdbLookup(): never
    {
        $data = $_POST['data'] ?? [];
        $values = $this->tmdbFieldValues($data);
        $message = null;

        $title = trim($data['title'] ?? '');
        if ($title === '') {
            $message = ['type' => 'error', 'text' => 'Bitte zuerst einen Titel eingeben.'];
        } else {
            $type = MovieType::tryFrom($data['type'] ?? '') ?? MovieType::Film;

            try {
                $tmdbMovie = TmdbClient::lookup(title: $title, type: $type);
            } catch (Throwable $throwable) {
                error_log('[movie-tracker] TMDB-Lookup fehlgeschlagen: ' . $throwable->getMessage());
                $tmdbMovie = null;
            }

            if ($tmdbMovie === null) {
                $message = ['type' => 'error', 'text' => 'TMDB-Abfrage ohne Ergebnis für "' . $title . '". Bei wiederholten Fehlern das Server-Log prüfen.'];
            } else {
                $values = [
                    'title' => $tmdbMovie['title'] !== '' ? $tmdbMovie['title'] : $values['title'],
                    'original_title' => $tmdbMovie['original_title'],
                    'year' => $tmdbMovie['year'] ?? $values['year'],
                    'url' => $tmdbMovie['url'],
                    'cast' => implode(PHP_EOL, $tmdbMovie['cast']),
                ];
                $message = ['type' => 'success', 'text' => 'Von TMDB übernommen: ' . $tmdbMovie['title']];
            }
        }

        Response::html(Template::render(template: 'movies/_tmdb-fields.html.php', variables: [
            'values' => $values,
            'message' => $message,
            'tmdbAvailable' => true,
        ]));
    }

    protected function renderForm(?object $movie, ?string $savedMessage = null, ?string $errorMessage = null, bool $partial = false): never
    {
        // Nach einem fehlgeschlagenen Speichern steckt der Cast im Eingabe-Objekt,
        // sonst kommt er aus der Datenbank.
        if ($movie !== null && isset($movie->castInput)) {
            $cast = $this->splitCastNames($movie->castInput);
        } elseif ($movie !== null && !empty($movie->id)) {
            $cast = $this->movieRepository->castNames(intval($movie->id));
        } else {
            $cast = [];
        }

        $isEdit = $movie !== null && !empty($movie->id);
        $variables = [
            'movie' => $movie,
            'cast' => $cast,
            'series' => $this->movieRepository->seriesTitles(),
            'savedMessage' => $savedMessage,
            'errorMessage' => $errorMessage,
            'tmdbAvailable' => TmdbClient::isConfigured(),
        ];

        if ($partial) {
            Response::html(Template::render(template: 'movies/form.html.php', variables: $variables));
        }

        Response::html(Template::page(
            template: 'movies/form.html.php',
            variables: $variables,
            title: $isEdit ? 'Film bearbeiten' : 'Film hinzufügen',
            activeNavigation: $isEdit ? 'movies' : 'add'
        ));
    }

    /** Baut aus den Formulardaten ein Anzeige-Objekt, damit Eingaben nach einem Fehler erhalten bleiben. */
    protected function movieFromInput(array $data, ?int $id = null): object
    {
        $movie = new \stdClass();
        $movie->id = $id;
        foreach (MovieRepository::FIELDS as $field) {
            $movie->$field = $data[$field] ?? null;
        }
        $movie->castInput = $data['cast'] ?? '';

        return $movie;
    }

    /** Startwerte der TMDB-Felder aus den aktuell eingegebenen Formulardaten */
    protected function tmdbFieldValues(array $data): array
    {
        return [
            'title' => trim($data['title'] ?? ''),
            'original_title' => trim($data['original_title'] ?? ''),
            'year' => trim($data['year'] ?? ''),
            'url' => trim($data['url'] ?? ''),
            'cast' => $data['cast'] ?? '',
        ];
    }

    /** @return string[] */
    protected function splitCastNames(string $cast): array
    {
        return preg_split(pattern: '/\R/', subject: $cast) ?: [];
    }
}
