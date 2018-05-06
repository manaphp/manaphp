<?php
namespace ManaPHP\Configuration\Configure\Engine;

use ManaPHP\Configuration\Configure\Engine\Json\Exception as JsonException;
use ManaPHP\Configuration\Configure\EngineInterface;

/**
 * Class ManaPHP\Configuration\Configure\Engine\Json
 *
 * @package configure\engine
 */
class Json implements EngineInterface
{
    /**
     * @param string $file
     *
     * @return array
     * @throws \ManaPHP\Configuration\Configure\Engine\Json\Exception
     */
    public function load($file)
    {
        $data = file_get_contents($file, true);
        if ($data === false) {
            throw new JsonException(['`:file` configure file can not be loaded', 'file' => $file]);
        } else {
            return json_decode($data, true);
        }
    }
}