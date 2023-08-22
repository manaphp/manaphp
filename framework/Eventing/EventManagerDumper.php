<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Dumping\Dumper;

class EventManagerDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        return ['*events' => array_keys($data['events'])];
    }
}