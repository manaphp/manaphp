<?php
declare(strict_types=1);

namespace ManaPHP\Mvc;

use ManaPHP\Dumping\Dumper;

class ViewDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        $data['context']['content'] = '***';
        unset($data['exists_cache']);

        return $data;
    }
}