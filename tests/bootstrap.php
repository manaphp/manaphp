<?php

declare(strict_types=1);

/**
 * PHPUnit bootstrap file
 * Loads Composer autoloader for framework classes
 */

// Load Composer autoloader from app-admin vendor directory
$autoloadFile = __DIR__ . '/../app-admin/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require $autoloadFile;
} else {
    throw new RuntimeException('Composer autoload file not found. Please run "composer install" in app-admin directory.');
}