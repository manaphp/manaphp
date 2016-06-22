<?php

namespace ManaPHP\Mvc\View\Renderer\Engine {

    use ManaPHP\Component;
    use ManaPHP\Di;
    use ManaPHP\Mvc\View\Renderer\EngineInterface;

    class Smarty extends Component implements EngineInterface
    {
        /** @noinspection PhpUndefinedClassInspection */
        /**
         * @var \Smarty
         */
        protected $_smarty;

        public function __construct($dependencyInjector = null)
        {
            parent::__construct($dependencyInjector);

            if (class_exists('\Smarty')) {
                /** @noinspection PhpUndefinedClassInspection */
                /** @noinspection PhpUndefinedMethodInspection */
                $this->_smarty = (new \Smarty())
                    ->setCompileDir($this->alias->resolve('@data/smarty/templates_c'))
                    ->setCacheDir($this->alias->resolve('@data/smarty/caches'))
                    ->setConfigDir($this->alias->resolve('@data/smarty/configs'))
                    ->setDebugging($this->configure->debug);
            } else {
                throw new Exception('\smarty class is not exists, please install it first.');
            }
        }

        public function render($file, $vars = null, $directOutput = true)
        {
            $smarty = $this->_dependencyInjector->getShared('smarty');
            /** @noinspection PhpUndefinedMethodInspection */
            $smarty->assign($vars);
            if ($directOutput) {
                /** @noinspection PhpUndefinedMethodInspection */
                $smarty->display($file);
                return null;
            } else {
                /** @noinspection PhpUndefinedMethodInspection */
                return $smarty->fetch($file);
            }
        }

        /** @noinspection PhpUndefinedClassInspection */
        /**
         * @return \Smarty
         */
        public function getSmarty()
        {
            return $this->_smarty;
        }
    }
}