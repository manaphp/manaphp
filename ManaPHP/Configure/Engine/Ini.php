<?php
namespace ManaPHP\Configure\Engine {

    use ManaPHP\Configure\EngineInterface;

    class Ini implements EngineInterface
    {
        public function load($file)
        {
            $data = parse_ini_file($file, true);
            if ($data === false) {
                throw new Exception("Configure file '$file' can't be loaded");
            }

            return $data;
        }
    }
}