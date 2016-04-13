<?php
namespace ManaPHP\Configure {

    use ManaPHP\Component;
    use ManaPHP\Di;

    /**
     * Class Configure
     *
     * @package ManaPHP
     */
    class Configure extends Component implements ConfigureInterface
    {
        public $debug = true;

        protected $_aliases = [];

        /**
         * Configure constructor.
         *
         * @param \ManaPHP\DiInterface $dependencyInjector
         *
         * @throws \ManaPHP\Di\Exception|\ManaPHP\Configure\Exception
         */
        public function __construct($dependencyInjector = null)
        {
            $this->_dependencyInjector = $dependencyInjector ?: Di::getDefault();

            if ($this->_dependencyInjector !== null && $this->_dependencyInjector->has('application')) {
                $this->setAlias('@app', $this->application->getAppPath());
                $this->setAlias('@data', $this->application->getDataPath());
            }
        }

        /**
         * @param string $name
         * @param string $path
         *
         * @return static
         * @throws \ManaPHP\Configure\Exception
         */
        public function setAlias($name, $path)
        {
            if ($name[0] !== '@') {
                throw new Exception('alias must start with @ character');
            }

            $this->_aliases[$name] = $this->resolvePath($path);

            return $this;
        }

        /**
         * @param string $name
         *
         * @return string|null
         * @throws \ManaPHP\Configure\Exception
         */
        public function getAlias($name)
        {
            if ($name[0] !== '@') {
                throw new Exception('alias must start with @ character');
            }

            return isset($this->_aliases[$name]) ? $this->_aliases[$name] : null;
        }

        /**
         * @param string $name
         *
         * @return bool
         * @throws \ManaPHP\Configure\Exception
         */
        public function hasAlias($name)
        {
            if ($name[0] !== '@') {
                throw new Exception('alias must start with @ character');
            }

            return isset($this->_aliases[$name]);
        }

        /**
         * @param string $path
         *
         * @return string
         * @throws \ManaPHP\Configure\Exception
         */
        public function resolvePath($path)
        {
            $path = str_replace('\\', '/', rtrim($path, '\\/'));

            if ($path[0] !== '@') {
                return $path;
            }

            list($alias) = explode('/', $path, 2);
            if (!isset($alias)) {
                throw new Exception("alias $alias is not exists: " . $path);
            }

            return str_replace($alias, $this->_aliases[$alias], $path);
        }
    }
}