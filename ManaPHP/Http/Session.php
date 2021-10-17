<?php

namespace ManaPHP\Http;

(static function () {
    $class = 'ManaPHP\Http\Session\Adapter\File';

    class_alias($class, 'ManaPHP\Http\Session');
})();