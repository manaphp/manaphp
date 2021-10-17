<?php

namespace ManaPHP\Data;

(static function () {
    class_alias('ManaPHP\Data\RedisInterface', 'ManaPHP\Data\RedisBrokerInterface');
})();