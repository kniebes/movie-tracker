<?php declare(strict_types=1);

namespace Kniebes\MovieTracker\Storage;

/**
 * Zentrale Namen aller Datenbank-Tabellen, damit SQL-Statements
 * keine verstreuten String-Literale enthalten.
 */
final class Table
{
    public const string MOVIE = 'movie';
    public const string MOVIE_CAST = 'movie_cast';
    public const string MOVIE_CAST_RELATION = 'movie_cast_relation';
    public const string SESSION = 'movie_tracker_session';
}
