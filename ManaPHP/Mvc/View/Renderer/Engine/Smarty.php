<?php

namespace ManaPHP\Mvc\View\Renderer\Engine {

    use ManaPHP\Component;
    use ManaPHP\Di;
    use ManaPHP\Mvc\View\Renderer\EngineInterface;

    class Smarty extends Component implements EngineInterface
    {
        /**
         * @var \Smarty
         */
        protected $_smarty;

        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: Di::getDefault();

            if (class_exists('\Smarty')) {
                $this->_smarty = (new \Smarty())
                    ->setCompileDir($this->configure->resolvePath('@data/smarty/templates_c'))
                    ->setCacheDir($this->configure->resolvePath('@data/smarty/caches'))
                    ->setConfigDir($this->configure->resolvePath('@data/smarty/configs'))
                    ->setDebugging($this->configure->debug);
            } else {
                throw new Exception('\smarty class is not exists, please install it first.');
            }
        }

        public function render($file, $vars = null, $directOutput = true)
        {
            $smarty = $this->_dependencyInjector->getShared('smarty');
            $smarty->assign($vars);
            if ($directOutput) {
                $smarty->display($file);
                return null;
            } else {
                return $smarty->fetch($file);
            }
        }

        /**
         * @return \Smarty
         */
        public function getSmarty()
        {
            return $this->_smarty;
        }
    }
}