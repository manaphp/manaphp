<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Dumping\Dumper;

class GlobalsDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        if (DIRECTORY_SEPARATOR === '\\') {
            foreach (['PATH', 'SystemRoot', 'COMSPEC', 'PATHEXT', 'WINDIR'] as $name) {
                unset($data['context']['_SERVER'][$name]);
            }
        }

        return $data;
    }
}