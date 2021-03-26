#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';

$app = new App\Application();
$app->cli();
