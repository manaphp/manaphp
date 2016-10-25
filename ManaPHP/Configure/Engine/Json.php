<?php
namespace ManaPHP\Configure\Engine;

use ManaPHP\Configure\Engine\Json\Exception as JsonException;
use ManaPHP\Configure\EngineInterface;

/**
 * Class ManaPHP\Configure\Engine\Json
 *
 * @package configure\engine
 */
class Json implements EngineInterface
{
    /**
     * @param string $file
     *
     * @return mixed
     * @throws \ManaPHP\Configure\Engine\Json\Exception
     */
    public function load($file)
    {
        $data = file_get_contents($file, true);
        if ($data === false) {
            throw new JsonException('`:file` configure file can not be loaded'/**m0db3b2b5cb242975b*/, ['file' => $file]);
        } else {
            return json_decode($data, true);
        }
    }
}