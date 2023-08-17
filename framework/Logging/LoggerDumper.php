<?php
declare(strict_types=1);

namespace ManaPHP\Logging;

use ManaPHP\Dumping\Dumper;

class LoggerDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        unset($data['logs'], $data['last_write']);

        return $data;
    }
}