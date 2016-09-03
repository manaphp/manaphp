<?php
namespace ManaPHP\Renderer\Engine;

use ManaPHP\Renderer\Engine\Exception as EngineException;
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
            throw new EngineException('read `:file` template file failed', ['file' => $file]);
        }

        echo $str;
    }
}