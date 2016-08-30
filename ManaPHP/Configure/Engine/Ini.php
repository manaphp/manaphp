<?php
namespace ManaPHP\Configure\Engine;

use ManaPHP\Configure\EngineInterface;

class Ini implements EngineInterface
{
    public function load($file)
    {
        $data = parse_ini_file($file, true);
        if ($data === false) {
            throw new Exception('`:file` configure file can not be loaded'/**m0a0e54c0c2a796b88*/, ['file' => $file]);
        }

        return $data;
    }
}