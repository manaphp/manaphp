<?php

namespace ManaPHP\Ws;

(static function () {
    $class = 'ManaPHP\Ws\Server\Adapter\Swoole';

    class_alias($class, 'ManaPHP\Ws\Server');
})();