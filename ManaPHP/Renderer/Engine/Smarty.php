<?php

namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

/**
 * Class ManaPHP\Renderer\Engine\Smarty
 *
 * @package renderer\engine
 */
class Smarty extends Component implements EngineInterface
{
    /**
     * @param string $file
     * @param array  $vars
     *
     * @return void
     */
    public function render($file, $vars = [])
    {
        if (!isset($this->smarty)) {
            $this->_di->setShared('smarty', 'Smarty');
            /** @noinspection PhpUndefinedFieldInspection */
            $this->smarty->setCompileDir($this->alias->resolve('@data/smarty/templates_c'))
                ->setCacheDir($this->alias->resolve('@data/smarty/caches'))
                ->setConfigDir($this->alias->resolve('@data/smarty/configs'))
                ->setDebugging($this->configure->debug);
        }

        $this->smarty->assign($vars)->display($file);
    }
}