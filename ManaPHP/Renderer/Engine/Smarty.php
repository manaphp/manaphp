<?php

namespace ManaPHP\Renderer\Engine;

use ManaPHP\Component;
use ManaPHP\Renderer\EngineInterface;

class Smarty extends Component implements EngineInterface
{
    /**
     * @param string $file
     * @param array  $vars
     */
    public function render($file, $vars = [])
    {
        if (!isset($this->smarty)) {
            $this->_dependencyInjector->setShared('smarty', 'Smarty');
            /** @noinspection PhpUndefinedFieldInspection */
            $this->smarty->setCompileDir($this->alias->resolve('@data/Smarty/templates_c'))
                ->setCacheDir($this->alias->resolve('@data/Smarty/caches'))
                ->setConfigDir($this->alias->resolve('@data/Smarty/configs'))
                ->setDebugging($this->configure->debug);
        }

        $this->smarty->assign($vars)->display($file);
    }
}