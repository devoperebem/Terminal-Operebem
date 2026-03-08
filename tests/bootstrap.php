<?php
// PHPUnit bootstrap
$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';

// Load environment (if needed in tests)
if (file_exists($root . '/.env')) {
    try {
        $dotenv = Dotenv\Dotenv::createImmutable($root);
        $dotenv->safeLoad();
    } catch (Throwable $e) {
        // ignore
    }
}
