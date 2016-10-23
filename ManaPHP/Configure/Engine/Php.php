<?php
namespace ManaPHP\Configure\Engine;

use ManaPHP\Configure\EngineInterface;

/**
 * Class ManaPHP\Configure\Engine\Php
 *
 * @package ManaPHP\Configure\Engine
 */
class Php implements EngineInterface
{
    /**
     * @param string $file
     *
     * @return mixed
     */
    public function load($file)
    {
        /** @noinspection PhpIncludeInspection */
        return require $file;
    }
}