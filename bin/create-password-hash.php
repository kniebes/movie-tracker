#!/usr/bin/env php
<?php declare(strict_types=1);

if (!isset($argv[1]) || trim($argv[1]) === '') {
    fwrite(STDERR, 'Aufruf: php bin/create-password-hash.php <passwort>' . PHP_EOL);
    exit(1);
}

// Einfache Anführungszeichen sind Pflicht: Symfony Dotenv würde die $-Zeichen
// des Bcrypt-Hashes sonst als Variablen-Referenzen interpolieren.
$hash = password_hash($argv[1], PASSWORD_BCRYPT, ['cost' => 12]);
echo 'In die .env übernehmen (inklusive Anführungszeichen):' . PHP_EOL;
echo 'AUTH_PASSWORD_HASH=' . "'" . $hash . "'" . PHP_EOL;
