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
<script>
    (function () {
        let hideTimeout = null;

        function showErrorToast(message) {
            const toast = document.getElementById('error-toast');
            toast.textContent = message;
            toast.hidden = false;
            clearTimeout(hideTimeout);
            hideTimeout = setTimeout(function () { toast.hidden = true; }, 6000);
        }

        // htmx swappt bei Fehler-Antworten nichts; ohne diese Meldung
        // sähe eine fehlgeschlagene Aktion wie ein stiller Erfolg aus.
        document.addEventListener('htmx:responseError', function (event) {
            const status = event.detail.xhr ? event.detail.xhr.status : '?';
            showErrorToast('Aktion fehlgeschlagen (HTTP ' + status + '). Details stehen im Server-Log.');
        });

        document.addEventListener('htmx:sendError', function () {
            showErrorToast('Server nicht erreichbar. Bitte Verbindung prüfen und erneut versuchen.');
        });
    })();
</script>

</body>
</html>
