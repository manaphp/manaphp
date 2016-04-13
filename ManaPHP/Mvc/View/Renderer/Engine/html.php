<?php
namespace ManaPHP\Mvc\View\Renderer\Engine {

    use ManaPHP\Mvc\View\Renderer\EngineInterface;

    class Html implements EngineInterface
    {

        public function __construct($dependencyInjector = null)
        {

        }

        public function render($file, $vars = null)
        {
            $str = file_get_contents($file);
            if ($str === false) {
                throw new Exception('Read template file failed: ', $file);
            }

            echo $str;
        }
    }
}