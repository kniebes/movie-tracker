<?php

/**
 * @var object $castMember
 * @var ?string $error
 */

$castId = intval($castMember->id);

?>
<form class="cast-row editing" id="cast-row-<?= $castId ?>"
      hx-put="/cast/<?= $castId ?>" hx-target="#cast-row-<?= $castId ?>" hx-swap="outerHTML">

    <?php if (!empty($error)): ?>
        <p class="form-message error"><?= escape($error) ?></p>
    <?php endif; ?>

    <div class="cast-edit-fields">
        <div class="field">
            <label for="inputCastName-<?= $castId ?>">Name</label>
            <input id="inputCastName-<?= $castId ?>" type="text" name="name" value="<?= escape($castMember->name) ?>" required autofocus>
        </div>
        <div class="field">
            <label for="inputCastUrl-<?= $castId ?>">URL</label>
            <input id="inputCastUrl-<?= $castId ?>" type="url" name="url" value="<?= escape($castMember->url) ?>" placeholder="https://…">
        </div>
    </div>
    <div class="cast-actions">
        <button class="button small" type="submit">Speichern</button>
        <button class="button ghost small" type="button"
                hx-get="/cast/<?= $castId ?>" hx-target="#cast-row-<?= $castId ?>" hx-swap="outerHTML">
            Abbrechen
        </button>
    </div>
</form>
