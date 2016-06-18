<?php
namespace ManaPHP {

    use \ManaPHP\Alias\Exception;
    use ManaPHP\Utility\Text;

    class Alias implements AliasInterface
    {
        protected $_aliases = [];

        public function __construct()
        {
            $this->set('@manaphp', str_replace('\\', '/', __DIR__));

            $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

            $found = false;
            foreach ($traces as $trace) {
                if (isset($trace['class']) && !Text::startsWith($trace['class'], 'ManaPHP\\')) {
                    $class=str_replace('\\','/',$trace['class']);
                    foreach (get_included_files() as $file) {
                        $file=str_replace('\\','/',$file);

                        if (Text::contains($file, $class . '.php')) {
                            $dir = dirname($file);

                            $this->set('@app', str_replace('\\', '/', $dir));
                            $this->set('@data', dirname($dir) . '/Data');

                            $found = true;
                            break;
                        }
                    }
                }

                if ($found) {
                    break;
                }
            }
        }

        /**
         * @param string $name
         * @param string $path
         *
         * @return string
         * @throws \ManaPHP\Alias\Exception
         */
        public function set($name, $path)
        {
            if ($name[0] !== '@') {
                throw new Exception('alias must start with @ character');
            }

            $this->_aliases[$name] = $this->resolve($path);
        }

        /**
         * @param string $name
         *
         * @return string|false
         * @throws \ManaPHP\Alias\Exception
         */
        public function get($name)
        {
            if ($name[0] !== '@') {
                throw new Exception('alias must start with @ character');
            }

            return isset($this->_aliases[$name]) ? $this->_aliases[$name] : false;
        }

        /**
         * @param string $name
         *
         * @return bool
         * @throws \ManaPHP\Alias\Exception
         */
        public function has($name)
        {
            if ($name[0] !== '@') {
                throw new Exception('alias must start with @ character');
            }

            return isset($this->_aliases[$name]);
        }

        /**
         * @param $path
         *
         * @return mixed
         * @throws \ManaPHP\Alias\Exception
         */
        public function resolve($path)
        {
            if (rtrim($path, '\\/') !== $path) {
                throw new Exception("Path can not end with '/' or '\\': " . $path);
            }

            $path = str_replace('\\', '/', $path);

            if ($path[0] !== '@') {
                return $path;
            }

            list($alias) = explode('/', $path, 2);
            if (!isset($this->_aliases[$alias])) {
                throw new Exception("alias $alias is not exists: " . $path);
            }

            return str_replace($alias, $this->_aliases[$alias], $path);
        }
    }
}