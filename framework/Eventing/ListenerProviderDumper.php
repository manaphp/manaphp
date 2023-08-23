<?php
declare(strict_types=1);

namespace ManaPHP\Eventing;

use ManaPHP\Dumping\Dumper;

class ListenerProviderDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        return ['*listeners' => array_keys($data['listeners'])];
    }
}