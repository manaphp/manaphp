<?php
namespace ManaPHP\Configuration\Configure\Engine;

use ManaPHP\Configuration\Configure\EngineInterface;

/**
 * Class ManaPHP\Configuration\Configure\Engine\Php
 *
 * @package configure\engine
 */
class Php implements EngineInterface
{
    /**
     * @param string $file
     *
     * @return array
     */
    public function load($file)
    {
        /** @noinspection PhpIncludeInspection */
        return require $file;
    }
}