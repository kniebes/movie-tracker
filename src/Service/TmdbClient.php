<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Kniebes\MovieTracker\Enum\MovieType;
use RuntimeException;

class TmdbClient
{
    private const string BASE_URL = 'https://api.themoviedb.org/3/';
    private const string WEB_URL = 'https://www.themoviedb.org/';
    private const int CAST_LIMIT = 10;

    /**
     * Sucht einen Titel bei TMDB und liefert die für das Filmformular relevanten
     * Felder (inkl. Cast) des besten Treffers zurück, oder null.
     *
     * @return array{tmdbId:int, title:string, original_title:string, year:?int, url:string, cast:string[]}|null
     */
    public static function lookup(string $title, MovieType $type = MovieType::Film): ?array
    {
        $endpoint = self::createEndpoint($type);
        $searchTerm = self::sanitizeSearch($title);

        $hit = self::firstSearchHit(endpoint: $endpoint, query: $searchTerm);
        if ($hit === null) {
            return null;
        }

        return self::loadDetails(endpoint: $endpoint, tmdbId: (int) $hit['id']);
    }

    public static function isConfigured(): bool
    {
        return !empty($_ENV['TMDB_API_KEY']);
    }

    protected static function createEndpoint(MovieType $type): string
    {
        return in_array($type, [MovieType::Episode, MovieType::Serie], strict: true) ? 'tv' : 'movie';
    }

    protected static function sanitizeSearch(string $title): string
    {
        $patterns = [
            '/\s*[-–]?\s*Staffel\s+\d+/iu',   // "- Staffel 4"
            '/\s*\(S\d{1,2}\)/i',              // "(S02)"
            '/\s*[-–]\s*Teil\s+\d+/iu',        // "- Teil 2"
            '/\s*Teil\s+\d+\s*$/iu',           // "Teil 2" am Ende
        ];

        $cleaned = preg_replace($patterns, '', $title);

        return trim($cleaned);
    }

    /**
     * Sucht erst auf Deutsch, dann ohne Sprachfilter, und gibt den ersten Treffer zurück.
     */
    protected static function firstSearchHit(string $endpoint, string $query): ?array
    {
        foreach (['de-DE', null] as $language) {
            $parameters = [
                'query' => $query,
                'include_adult' => 'false',
            ];
            if ($language !== null) {
                $parameters['language'] = $language;
            }

            $data = self::request(path: 'search/' . $endpoint, parameters: $parameters);
            $results = $data['results'] ?? [];
            if ($results !== []) {
                return $results[0];
            }
        }

        return null;
    }

    /**
     * Lädt Detail- und Cast-Daten für einen Treffer und mappt sie auf die Formularfelder.
     */
    protected static function loadDetails(string $endpoint, int $tmdbId): ?array
    {
        $data = self::request(
            path: $endpoint . '/' . $tmdbId,
            parameters: [
                'language' => 'de-DE',
                'append_to_response' => 'credits',
            ]
        );
        if ($data === null) {
            return null;
        }

        $isSeries = $endpoint === 'tv';
        $titleField = $isSeries ? 'name' : 'title';
        $originalField = $isSeries ? 'original_name' : 'original_title';
        $dateField = $isSeries ? 'first_air_date' : 'release_date';

        $date = $data[$dateField] ?? '';
        $year = strlen($date) >= 4 ? (int) substr($date, 0, 4) : null;

        $cast = [];
        foreach (array_slice($data['credits']['cast'] ?? [], 0, self::CAST_LIMIT) as $member) {
            if (!empty($member['name'])) {
                $cast[] = $member['name'];
            }
        }

        return [
            'tmdbId' => $tmdbId,
            'title' => (string) ($data[$titleField] ?? ''),
            'original_title' => (string) ($data[$originalField] ?? ''),
            'year' => $year,
            'url' => self::WEB_URL . $endpoint . '/' . $tmdbId,
            'cast' => $cast,
        ];
    }

    /**
     * Führt einen GET-Request gegen die TMDB-API aus und gibt das dekodierte JSON zurück.
     */
    protected static function request(string $path, array $parameters): ?array
    {
        $apiKey = $_ENV['TMDB_API_KEY'] ?? null;
        if (empty($apiKey)) {
            throw new RuntimeException('Missing TMDB_API_KEY');
        }

        $parameters['api_key'] = $apiKey;

        $client = new Client([
            'base_uri' => self::BASE_URL,
            'timeout' => 15,
        ]);

        try {
            $response = $client->get($path, ['query' => $parameters]);
        } catch (GuzzleException) {
            return null;
        }

        $data = json_decode((string) $response->getBody(), associative: true);

        return is_array($data) ? $data : null;
    }
}
