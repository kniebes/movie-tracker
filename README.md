# Movie Tracker

Ein kleines, selbst gehostetes Logbuch für gesehene Filme, Serien und Episoden. PHP 8.3+, MySQL/MariaDB, UI mit [htmx](https://htmx.org), ohne Frontend-Build.

## Features

- Filme, Serien und Episoden erfassen, mit Bewertung (halbe Sterne), Seh-Datum und Kommentar
- Liste als Logbuch mit Monatsgruppen, Live-Suche und Typ-Filter
- Darsteller-Verwaltung mit Inline-Editing, automatisch gepflegt über das Filmformular (ein Name je Zeile)
- Optionaler TMDB-Lookup: Titel, Originaltitel, Jahr, URL und Cast automatisch übernehmen
- Einfacher Login mit einem Benutzer (Token-Cookie, bcrypt-Hash in der `.env`)

## Setup

1. Abhängigkeiten installieren:

    ```sh
    composer install
    ```

2. Datenbank anlegen und Schemata importieren:

    ```sh
    mysql -e 'CREATE DATABASE movie_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
    cat schema/movie.sql schema/movie_cast.sql schema/movie_cast_relation.sql schema/movie_tracker_session.sql schema/movie_tracker_login_attempt.sql | mysql movie_tracker
    ```

3. Konfiguration:

    ```sh
    cp .env.dist .env
    php bin/create-password-hash.php 'mein-passwort'
    ```

    In der `.env` die Datenbank-Zugangsdaten, `AUTH_USERNAME` und den erzeugten `AUTH_PASSWORD_HASH` eintragen. Für den TMDB-Lookup einen API-Key von [themoviedb.org](https://www.themoviedb.org/settings/api) als `TMDB_API_KEY` hinterlegen; ohne Key wird der Lookup-Button einfach ausgeblendet.

4. Starten (lokal):

    ```sh
    php -S localhost:8080 -t public
    ```

    Auf einem Webserver zeigt die Document-Root auf `public/`, alle Anfragen (außer existierenden Dateien) gehen an `public/index.php`. Für Apache erledigt das die mitgelieferte `public/.htaccess` (Rewrite auf den Front-Controller plus Security-Header), es muss nur `AllowOverride` für das Verzeichnis erlaubt sein.

## Entwicklung mit DDEV

Alternativ zum Built-in-Server bringt das Projekt eine [DDEV](https://ddev.com)-Konfiguration mit (Apache-FPM, PHP 8.5, MariaDB), die dem Live-Setup deutlich näher kommt:

```sh
ddev start
cat schema/*.sql | ddev mysql
```

Die App läuft dann unter <https://movie-tracker.ddev.site>. In der `.env` gelten die DDEV-Zugangsdaten: `STORAGE_HOST=db`, Datenbank, Benutzer und Passwort jeweils `db`.

CLI-Skripte, die die Datenbank brauchen, müssen im Container laufen, weil der Host den DB-Hostnamen `db` nicht auflöst:

```sh
ddev exec php bin/update-movie-cast-urls.php
```

## Architektur

- `public/index.php`: Front-Controller mit Routing-Tabelle und Auth-Check
- `src/Controller/`: eine Klasse je Bereich (Filme, Darsteller, Login)
- `src/Repository/`: Datenzugriff über einen schlanken PDO-Wrapper (`src/Storage/Storage.php`)
- `templates/`: PHP-Templates; Fragmente mit `_`-Präfix werden von htmx nachgeladen bzw. geswappt
- htmx liegt lokal unter `public/assets/htmx.min.js`, es gibt keine externen Frontend-Abhängigkeiten
