<?php

use Kniebes\MovieTracker\Enum\MovieType;

/**
 * @var string $rows
 * @var string $query
 * @var ?string $type
 */

?>
<section class="page-head">
    <h1>Filme</h1>
</section>

<form class="filter-bar" hx-get="/movies" hx-target="#movie-list" hx-swap="innerHTML"
      hx-trigger="input changed delay:300ms, search" hx-push-url="true" hx-indicator="#search-indicator">
    <input type="search" name="q" placeholder="Titel suchen" value="<?= escape($query) ?>" aria-label="Titel suchen">
    <select name="type" aria-label="Nach Typ filtern">
        <option value="">Alle Typen</option>
        <?php foreach (MovieType::cases() as $movieType): ?>
            <option value="<?= escape($movieType->value) ?>"<?= $type === $movieType->value ? ' selected' : '' ?>><?= escape($movieType->label()) ?></option>
        <?php endforeach; ?>
    </select>
    <span id="search-indicator" class="htmx-indicator spinner" aria-hidden="true"></span>
</form>

<div id="movie-list" class="movie-list">
    <?= $rows ?>
</div>
