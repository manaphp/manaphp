<?php
namespace ManaPHP\Mvc\View\Renderer\Engine;

use ManaPHP\Mvc\View\Renderer\EngineInterface;

class Html implements EngineInterface
{
    /**
     * @param string $file
     * @param array  $vars
     *
     * @throws \ManaPHP\Mvc\View\Renderer\Engine\Exception
     */
    public function render($file, $vars = [])
    {
        $str = file_get_contents($file);
        if ($str === false) {
            throw new Exception('Read template file failed: ', $file);
        }

        echo $str;
    }
}