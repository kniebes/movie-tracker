<?php

/**
 * @var array{title:string, original_title:?string, year:mixed, url:?string, cast:string} $values
 * @var ?array{type:string, text:string} $message
 * @var bool $tmdbAvailable
 */

?>
<div id="tmdb-fields">
    <?php if ($message !== null): ?>
        <p class="form-message <?= escape($message['type']) ?>" role="status"><?= escape($message['text']) ?></p>
    <?php endif; ?>

    <div class="field">
        <label for="inputTitle">Titel</label>
        <div class="field-with-action">
            <input id="inputTitle" list="series-list" type="text" name="data[title]" value="<?= escape($values['title']) ?>" required>
            <?php if ($tmdbAvailable): ?>
                <button type="button" class="button ghost tmdb-button"
                        hx-post="/tmdb-lookup" hx-include="closest form"
                        hx-target="#tmdb-fields" hx-swap="outerHTML"
                        hx-indicator="#tmdb-indicator">
                    Von TMDB laden<span id="tmdb-indicator" class="htmx-indicator spinner" aria-hidden="true"></span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="field">
        <label for="inputOriginalTitle">Originaltitel</label>
        <input id="inputOriginalTitle" type="text" name="data[original_title]" value="<?= escape($values['original_title']) ?>">
    </div>

    <div class="field-row">
        <div class="field">
            <label for="inputYear">Jahr</label>
            <input id="inputYear" type="number" min="1888" max="2100" name="data[year]" value="<?= escape($values['year']) ?>">
        </div>
        <div class="field">
            <label for="inputUrl">URL</label>
            <input id="inputUrl" type="url" name="data[url]" value="<?= escape($values['url']) ?>">
        </div>
    </div>

    <div class="field">
        <label for="inputCast">Darsteller (einer je Zeile)</label>
        <textarea id="inputCast" name="data[cast]" rows="6"><?= escape($values['cast']) ?></textarea>
    </div>
</div>
