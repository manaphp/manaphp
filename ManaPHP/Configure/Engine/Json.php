<?php
namespace ManaPHP\Configure\Engine {

    use ManaPHP\Configure\EngineInterface;

    class Json implements EngineInterface
    {

        public function load($file)
        {
            $data = file_get_contents($file, true);
            if ($data === false) {
                throw new Exception("Configure file '$file' can't be loaded");
            } else {
                return $data;
            }
        }
    }
}