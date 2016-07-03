<?php

namespace ManaPHP\Mvc\View\Renderer\Engine {

    use ManaPHP\Component;
    use ManaPHP\Di;
    use ManaPHP\Mvc\View\Renderer\EngineInterface;

    class Smarty extends Component implements EngineInterface
    {
        public function render($file, $vars = null, $directOutput = true)
        {
            if (!isset($this->smarty) && !$this->_dependencyInjector->has('smarty')) {
                $this->_dependencyInjector->setShared('smarty', 'Smarty');
                /** @noinspection PhpUndefinedFieldInspection */
                $this->smarty->setCompileDir($this->alias->resolve('@data/Smarty/templates_c'))
                    ->setCacheDir($this->alias->resolve('@data/Smarty/caches'))
                    ->setConfigDir($this->alias->resolve('@data/Smarty/configs'))
                    ->setDebugging($this->configure->debug);
            }

            if ($directOutput) {
                /** @noinspection PhpUndefinedMethodInspection */
                $this->assign($vars)->display($file);
                return null;
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                return $this->assign($vars)->fetch($file);
            }
        }
    }
}