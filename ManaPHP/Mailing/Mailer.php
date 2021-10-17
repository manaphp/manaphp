<?php

namespace ManaPHP\Mailing;

(static function () {
    $class = 'ManaPHP\Mailing\Mailer\Adapter\Smtp';

    class_alias($class, 'ManaPHP\Mailing\Mailer');
})();