<?php

namespace ManaPHP\Caching;

(static function () {
    if (extension_loaded('redis')) {
        $class = 'ManaPHP\Caching\Cache\Adapter\Redis';
    } else {
        $class = 'ManaPHP\Caching\Cache\Adapter\File';
    }

    class_exists($class, 'ManaPHP\Caching\Cache');
})();