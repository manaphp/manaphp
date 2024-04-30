<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server;

use function extension_loaded;

class Detector
{
    public static function detect(): string
    {
        if (PHP_SAPI === 'cli') {
            if (class_exists('Workerman\Worker')) {
                return '#workerman';
            } elseif (extension_loaded('swoole')) {
                return '#swoole';
            } else {
                return '#php';
            }
        } elseif (PHP_SAPI === 'cli-server') {
            return '#php';
        } else {
            return '#fpm';
        }
    }
}