<?php
namespace ManaPHP\Renderer\Engine;

use ManaPHP\Renderer\EngineInterface;

class Html implements EngineInterface
{
    /**
     * @param string $file
     * @param array  $vars
     *
     * @throws \ManaPHP\Renderer\Engine\Exception
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