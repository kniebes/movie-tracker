<?php

/** @var ?string $error */

?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Anmelden · Movie Tracker</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body class="login-page">

<main class="login-card">
    <p class="brand">Movie<span class="brand-beam"></span>Tracker</p>

    <?php if (!empty($error)): ?>
        <p class="form-message error"><?= escape($error) ?></p>
    <?php endif; ?>

    <form action="/login" method="post">
        <div class="field">
            <label for="inputUsername">Benutzername</label>
            <input id="inputUsername" type="text" name="username" autocomplete="username" required autofocus>
        </div>
        <div class="field">
            <label for="inputPassword">Passwort</label>
            <input id="inputPassword" type="password" name="password" autocomplete="current-password" required>
        </div>
        <button class="button" type="submit">Anmelden</button>
    </form>
</main>

</body>
</html>
