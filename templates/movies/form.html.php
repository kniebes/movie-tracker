<?php

use Kniebes\MovieTracker\Enum\MovieType;
use Kniebes\MovieTracker\View\Template;

/**
 * @var ?object $movie null beim Anlegen; nach einem Speicherfehler die rohen Eingabewerte
 * @var string[] $cast
 * @var string[] $series
 * @var ?string $savedMessage
 * @var ?string $errorMessage
 * @var bool $tmdbAvailable
 */

$isEdit = $movie !== null && !empty($movie->id);
$currentType = $movie?->type ?? MovieType::Film->value;

?>
<div id="movie-editor">
    <section class="page-head">
        <h1><?= $isEdit ? 'Film bearbeiten' : 'Film hinzufügen' ?></h1>
        <?php if ($savedMessage !== null): ?>
            <p class="saved-flash" role="status"><?= escape($savedMessage) ?></p>
        <?php endif; ?>
    </section>

    <?php if ($errorMessage !== null): ?>
        <p class="form-message error" role="alert"><?= escape($errorMessage) ?></p>
    <?php endif; ?>

    <?php $formUrl = $isEdit ? '/movies/' . intval($movie->id) : '/movies'; ?>
    <form class="movie-form"
          action="<?= $formUrl ?>" method="post"
          hx-post="<?= $formUrl ?>" hx-target="#movie-editor" hx-swap="outerHTML show:top">

        <fieldset class="type-switch" aria-label="Typ">
            <?php foreach (MovieType::cases() as $movieType): ?>
                <label>
                    <input type="radio" name="data[type]" value="<?= escape($movieType->value) ?>"<?= $currentType === $movieType->value ? ' checked' : '' ?>>
                    <span><?= escape($movieType->label()) ?></span>
                </label>
            <?php endforeach; ?>
        </fieldset>

        <fieldset class="form-group">
            <legend>Werk</legend>

            <?= Template::render(template: 'movies/_tmdb-fields.html.php', variables: [
                'values' => [
                    'title' => $movie?->title ?? '',
                    'original_title' => $movie?->original_title ?? '',
                    'year' => $movie?->year ?? '',
                    'url' => $movie?->url ?? '',
                    'cast' => implode(PHP_EOL, $cast),
                ],
                'message' => null,
                'tmdbAvailable' => $tmdbAvailable,
            ]) ?>

            <datalist id="series-list">
                <?php foreach ($series as $seriesTitle): ?>
                    <option value="<?= escape($seriesTitle) ?>"></option>
                <?php endforeach; ?>
            </datalist>

            <div class="field-row">
                <div class="field">
                    <label for="inputSeries">Staffel</label>
                    <input id="inputSeries" type="number" min="0" name="data[series]" value="<?= escape($movie?->series) ?>">
                </div>
                <div class="field">
                    <label for="inputEpisode">Episode</label>
                    <input id="inputEpisode" type="number" min="0" name="data[episode]" value="<?= escape($movie?->episode) ?>">
                </div>
            </div>
        </fieldset>

        <fieldset class="form-group">
            <legend>Logbuch</legend>

            <div class="field-row">
                <div class="field">
                    <label for="inputSeen">Gesehen am</label>
                    <input id="inputSeen" type="date" name="data[seen]" value="<?= escape($movie?->seen ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="field">
                    <label for="inputRating">Bewertung (0 bis 5)</label>
                    <input id="inputRating" type="number" step="0.5" min="0" max="5" name="data[rating]" value="<?= escape($movie?->rating) ?>">
                </div>
            </div>

            <div class="field">
                <label for="inputComment">Kommentar</label>
                <textarea id="inputComment" name="data[comment]" rows="5"><?= escape($movie?->comment) ?></textarea>
            </div>
        </fieldset>

        <div class="form-actions">
            <button class="button" type="submit"><?= $isEdit ? 'Speichern' : 'Film anlegen' ?></button>
            <?php if ($isEdit): ?>
                <button class="button danger" type="button"
                        hx-delete="/movies/<?= intval($movie->id) ?>"
                        hx-confirm="Diesen Film wirklich löschen? Die Darsteller-Zuordnungen werden entfernt.">
                    Löschen
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>
