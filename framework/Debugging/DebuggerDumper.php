<?php

namespace ManaPHP\Debugging;

use ManaPHP\Dumping\Dumper;

class DebuggerDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        $data['context'] = array_keys($data['context']);

        return $data;
    }
}