<?php
declare(strict_types=1);

namespace ManaPHP\Rendering;

use ManaPHP\Dumping\Dumper;

class RendererDumper extends Dumper
{
    public function dump(object $object): array
    {
        $data = parent::dump($object);

        if (isset($data['context'])) {
            foreach ($data['context']['sections'] as $k => $v) {
                $data['context']['sections'][$k] = '***';
            }
        }

        $data['files'] = ['***'];
        unset($data['mutex']);

        return $data;
    }
}