<?php
namespace ManaPHP\Configure\Engine {

    use ManaPHP\Configure\EngineInterface;

    class Php implements EngineInterface
    {
        public function load($file)
        {
            /** @noinspection PhpIncludeInspection */
            return require $file;
        }
    }
}