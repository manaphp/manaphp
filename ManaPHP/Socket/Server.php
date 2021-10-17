<?php

namespace ManaPHP\Socket;

(static function () {
    $class = 'ManaPHP\Socket\Server\Adapter\Swoole';

    class_alias($class, 'ManaPHP\Socket\Server');
})();