<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Dumping\Dumper;

class ResponseDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        $data['context']['content'] = '***';
        if (isset($data['context']['headers']['X-Logger'])) {
            $data['context']['headers']['X-Logger'] = '***';
        }

        return $data;
    }
}