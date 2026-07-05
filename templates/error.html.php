<?php

/**
 * @var int $statusCode
 * @var string $message
 */

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fehler <?= intval($statusCode) ?> · Movie Tracker</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="login-page">

<main class="login-card error-card">
    <p class="brand">Movie<span class="brand-beam"></span>Tracker</p>
    <p class="error-code"><?= intval($statusCode) ?></p>
    <p class="error-message"><?= escape($message) ?></p>
    <a class="button" href="/movies">Zurück zur Filmliste</a>
</main>

</body>
</html>
