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

// Register autoloader for Tests namespace
spl_autoload_register(function ($class) {
    // Handle Tests namespace
    if (strpos($class, 'Tests\\') === 0) {
        // Remove 'Tests\' prefix (6 characters)
        $relativePath = substr($class, 6);
        // Convert namespace separators to directory separators
        $relativePath = str_replace('\\', '/', $relativePath);
        // Build full file path
        $file = __DIR__ . '/' . $relativePath . '.php';
        
        if (file_exists($file)) {
            require $file;
            return true;
        }
    }
    
    return false;
}, true, true);

