<?php

namespace ManaPHP\Messaging;

(static function () {
    if (extension_loaded('redis')) {
        $class = ' ManaPHP\Messaging\Queue\Adapter\Redis';
    } else {
        $class = 'ManaPHP\Messaging\Queue\Adapter\Db';
    }

    class_exists($class, 'ManaPHP\Messaging\Queue');
})();