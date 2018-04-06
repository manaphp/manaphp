<?php

namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Html
 *
 * @package renderer\engine
 */
class Html extends Component implements EngineInterface
{
    /**
     * Renders a view using the template engine
     *
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = [])
    {
        echo file_get_contents($file);
    }
}