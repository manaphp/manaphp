<?php
namespace ManaPHP\Configuration\Configure\Engine;

use ManaPHP\Configuration\Configure\Engine\Ini\Exception as IniException;
use ManaPHP\Configuration\Configure\EngineInterface;

/**
 * Class ManaPHP\Configuration\Configure\Engine\Ini
 *
 * @package configure\engine
 */
class Ini implements EngineInterface
{
    /**
     * @param string $file
     *
     * @return array
     * @throws \ManaPHP\Configuration\Configure\Engine\Ini\Exception
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