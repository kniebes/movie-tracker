<?php

use Kniebes\MovieTracker\Enum\MovieType;

/**
 * @var object[] $movies
 * @var bool $hasMore
 * @var string $query
 * @var ?string $type
 * @var int $nextOffset
 * @var string $previousMonthKey
 * @var bool $isFirstPage
 */

$currentMonthKey = $previousMonthKey;

?>
<?php if (empty($movies) && $isFirstPage): ?>

    <div class="empty-state">
        <?php if ($query !== '' || $type !== null): ?>
            <p>Keine Filme gefunden. Andere Suche probieren oder den Filter zurücksetzen.</p>
        <?php else: ?>
            <p>Das Logbuch ist noch leer.</p>
            <a class="button" href="/movies/new">Ersten Film eintragen</a>
        <?php endif; ?>
    </div>

<?php endif; ?>
<?php foreach ($movies as $movie): ?>
    <?php $monthKey = substr((string) $movie->seen, 0, 7); ?>
    <?php if ($monthKey !== $currentMonthKey): ?>
        <?php $currentMonthKey = $monthKey; ?>

        <h2 class="month-divider"><span><?= escape(formatMonthLabel($monthKey)) ?></span></h2>

    <?php endif; ?>
    <a class="movie-row" href="/movies/<?= intval($movie->id) ?>/edit">
        <span class="movie-main">
            <span class="movie-title"><?= escape($movie->title) ?></span>
            <?php if (!empty($movie->original_title) && $movie->original_title !== $movie->title): ?>
                <span class="movie-original"><?= escape($movie->original_title) ?></span>
            <?php endif; ?>
        </span>
        <span class="movie-meta">
            <span class="badge type-<?= escape($movie->type) ?>"><?= escape(MovieType::tryFrom($movie->type)?->label() ?? $movie->type) ?></span>
            <?php if ($movie->series !== null || $movie->episode !== null): ?>
                <span class="data"><?= $movie->series !== null ? 'S' . escape($movie->series) : '' ?><?= $movie->episode !== null ? ' E' . escape($movie->episode) : '' ?></span>
            <?php endif; ?>
            <?php if ($movie->year !== null): ?>
                <span class="data"><?= escape($movie->year) ?></span>
            <?php endif; ?>
            <?php if ($movie->rating !== null): ?>
                <span class="stars" role="img" aria-label="Bewertung: <?= escape(str_replace('.', ',', (string) floatval($movie->rating))) ?> von 5">
                    <span aria-hidden="true">★★★★★</span>
                    <span class="fill" aria-hidden="true" style="width: <?= max(0, min(100, floatval($movie->rating) / 5 * 100)) ?>%">★★★★★</span>
                </span>
            <?php endif; ?>
            <span class="data seen"><?= escape(formatShortDate((string) $movie->seen)) ?></span>
        </span>
    </a>
<?php endforeach; ?>
<?php if ($hasMore): ?>

    <?php
    $loadMoreUrl = '/movies?' . http_build_query([
        'q' => $query,
        'type' => $type ?? '',
        'offset' => $nextOffset,
        'after' => $currentMonthKey,
    ]);
    ?>
    <button class="button ghost load-more" hx-get="<?= escape($loadMoreUrl) ?>" hx-target="this" hx-swap="outerHTML">
        Mehr laden
    </button>

<?php endif; ?>
