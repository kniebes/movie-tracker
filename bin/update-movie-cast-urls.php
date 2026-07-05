#!/usr/bin/env php
<?php declare(strict_types=1);

use Kniebes\MovieTracker\Bootstrap\Environment;
use Kniebes\MovieTracker\Service\TmdbClient;
use Kniebes\MovieTracker\Storage\Storage;
use Kniebes\MovieTracker\Storage\Table;

require dirname(__DIR__) . '/vendor/autoload.php';

Environment::init(dirname(__DIR__));

if (!TmdbClient::isConfigured()) {
    fwrite(STDERR, 'Kein TMDB_API_KEY in der .env konfiguriert.' . PHP_EOL);
    exit(1);
}

$storage = Storage::getInstance();

$castMembers = $storage->select(
    sql: 'SELECT id, name FROM '.Table::MOVIE_CAST.' WHERE url IS NULL OR url = "" ORDER BY name'
);

$total = count($castMembers);
$updatedCount = 0;

if ($total === 0) {
    echo 'Keine offenen movie_cast-Einträge.' . PHP_EOL;
    exit;
}

foreach ($castMembers as $index => $castMember) {
    $position = $index + 1;

    try {
        $person = TmdbClient::lookupPerson($castMember->name);
    } catch (Throwable $throwable) {
        echo sprintf('[%d von %d] %s: Fehler (%s)', $position, $total, $castMember->name, $throwable->getMessage()) . PHP_EOL;
        continue;
    }

    if ($person === null) {
        echo sprintf('[%d von %d] %s: kein Treffer', $position, $total, $castMember->name) . PHP_EOL;
        continue;
    }

    $storage->execute(
        sql: 'UPDATE '.Table::MOVIE_CAST.' SET url = :url WHERE id = :id',
        parameters: ['url' => $person['url'], 'id' => $castMember->id]
    );
    $updatedCount++;

    echo sprintf('[%d von %d] %s: %s', $position, $total, $castMember->name, $person['url']) . PHP_EOL;
}

echo sprintf('Fertig. %d von %d aktualisiert.', $updatedCount, $total) . PHP_EOL;
