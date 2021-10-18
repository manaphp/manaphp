<?php

namespace ManaPHP\Logging;

(static function () {
    $class = 'ManaPHP\Logging\Logger\Adapter\File';

    class_alias($class, 'ManaPHP\Logging\Logger');
})();