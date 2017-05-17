<?php
namespace ManaPHP\Configure\Engine;

use ManaPHP\Configure\EngineInterface;

/**
 * Class ManaPHP\Configure\Engine\Php
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