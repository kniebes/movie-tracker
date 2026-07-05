<?php declare(strict_types=1);

if (!isset($argv[1]) || trim($argv[1]) === '') {
    fwrite(STDERR, 'Aufruf: php bin/create-password-hash.php <passwort>' . PHP_EOL);
    exit(1);
}

echo password_hash($argv[1], PASSWORD_BCRYPT, ['cost' => 12]) . PHP_EOL;
