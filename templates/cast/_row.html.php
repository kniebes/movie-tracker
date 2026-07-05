<?php

/** @var object $castMember */

$castId = intval($castMember->id);

?>
<div class="cast-row" id="cast-row-<?= $castId ?>">
    <div class="cast-main">
        <span class="cast-name">
            <?php if (!empty($castMember->url)): ?>
                <a href="<?= escape($castMember->url) ?>" target="_blank" rel="noopener"><?= escape($castMember->name) ?></a>
            <?php else: ?>
                <?= escape($castMember->name) ?>
            <?php endif; ?>
        </span>
        <span class="cast-movies"><?= intval($castMember->movieCount) > 0 ? escape($castMember->movies) : 'Keinem Film zugeordnet' ?></span>
    </div>
    <div class="cast-actions">
        <button class="button ghost small" type="button"
                hx-get="/cast/<?= $castId ?>/edit" hx-target="#cast-row-<?= $castId ?>" hx-swap="outerHTML">
            Bearbeiten
        </button>
        <button class="button danger small" type="button"
                hx-delete="/cast/<?= $castId ?>"
                hx-confirm="<?= escape($castMember->name) ?> wirklich löschen? Die Zuordnungen zu Filmen werden entfernt."
                hx-target="#cast-row-<?= $castId ?>" hx-swap="delete">
            Löschen
        </button>
    </div>
</div>
