<?php

namespace ManaPHP\Messaging;

(static function () {
    $class = 'ManaPHP\Messaging\PubSub\Adapter\Redis';

    class_alias($class, 'ManaPHP\Messaging\PubSub');
})();