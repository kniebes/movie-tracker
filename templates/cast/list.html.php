<?php

use Kniebes\MovieTracker\View\Template;

/** @var object[] $castMembers */

?>
<section class="page-head">
    <h1>Darsteller</h1>
    <p class="page-note"><?= count($castMembers) ?> Personen. Neue Darsteller entstehen automatisch beim Speichern eines Films.</p>
</section>

<?php if (empty($castMembers)): ?>

    <div class="empty-state">
        <p>Noch keine Darsteller. Sie werden beim Eintragen von Filmen automatisch angelegt.</p>
    </div>

<?php else: ?>

    <div class="cast-list">
        <?php foreach ($castMembers as $castMember): ?>
            <?= Template::render(template: 'cast/_row.html.php', variables: ['castMember' => $castMember]) ?>
        <?php endforeach; ?>
    </div>

<?php endif; ?>
