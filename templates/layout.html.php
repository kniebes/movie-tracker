<?php

/**
 * @var string $content
 * @var string $title
 * @var string $activeNavigation
 */

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= escape($title) ?> · Movie Tracker</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <!-- 422 (Validierungsfehler) soll htmx swappen, damit Inline-Fehlermeldungen ankommen -->
    <meta name="htmx-config" content='{"responseHandling":[{"code":"204","swap":false},{"code":"[23]..","swap":true},{"code":"422","swap":true,"error":false},{"code":"[45]..","swap":false,"error":true}]}'>
    <script src="/assets/htmx.min.js" defer></script>
    <script src="/assets/app.js" defer></script>
</head>
<body>

<header class="topbar">
    <a class="brand" href="/movies">Movie<span class="brand-beam"></span>Tracker</a>
    <nav class="main-navigation">
        <a href="/movies"<?= $activeNavigation === 'movies' ? ' class="active"' : '' ?>>Filme</a>
        <a href="/cast"<?= $activeNavigation === 'cast' ? ' class="active"' : '' ?>>Darsteller</a>
    </nav>
    <div class="topbar-actions">
        <a class="button" href="/movies/new">+ Film</a>
        <form action="/logout" method="post">
            <button class="button ghost" type="submit">Abmelden</button>
        </form>
    </div>
</header>

<main class="container">
    <?= $content ?>
</main>

<div id="error-toast" role="alert" hidden></div>

</body>
</html>
