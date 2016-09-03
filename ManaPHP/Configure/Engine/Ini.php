<?php
namespace ManaPHP\Configure\Engine;

use ManaPHP\Configure\Engine\Ini\Exception as IniException;
use ManaPHP\Configure\EngineInterface;

class Ini implements EngineInterface
{
    /**
     * @param string $file
     *
     * @return array
     * @throws \ManaPHP\Configure\Engine\Ini\Exception
     */
    public function load($file)
    {
        $data = parse_ini_file($file, true);
        if ($data === false) {
            throw new IniException('`:file` configure file can not be loaded'/**m0a0e54c0c2a796b88*/, ['file' => $file]);
        }

        return $data;
    }
}