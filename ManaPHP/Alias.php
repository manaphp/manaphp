<?php
namespace ManaPHP;

use ManaPHP\Alias\Exception;
use ManaPHP\Utility\Text;

class Alias extends Component implements AliasInterface
{
    /**
     * @var array
     */
    protected $_aliases = [];

    public function __construct()
    {
        if (isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] !== '') {
            $dir = dirname($_SERVER['DOCUMENT_ROOT']) . '/ManaPHP';
            if (is_dir($dir)) {
                $this->set('@manaphp', $dir);
            }
        } else {
            $this->set('@manaphp', str_replace('\\', '/', __DIR__));
        }

        /**
         * @var $traces array
         */
        $traces = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT);

        $found = false;
        foreach ($traces as $trace) {
            if (isset($trace['class']) && !Text::startsWith($trace['class'], 'ManaPHP\\')) {
                $class = str_replace('\\', '/', $trace['class']);
                foreach (get_included_files() as $file) {
                    $file = str_replace('\\', '/', $file);

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

        return $this->_aliases[$name];
    }

    /**
     * @param string $name
     *
     * @return bool|string
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

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @param string $path
     *
     * @return string
     */
    public function resolve($path)
    {
        if (rtrim($path, '\\/') !== $path) {

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new Exception("Path can not end with '/' or '\\': " . $path);
        }

        $path = str_replace('\\', '/', $path);

        if ($path[0] !== '@') {
            return $path;
        }

        $parts = explode('/', $path, 2);
        $alias = $parts[0];
        if (!isset($this->_aliases[$alias])) {

            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            throw new Exception("alias $alias is not exists: " . $path);
        }

        return str_replace($alias, $this->_aliases[$alias], $path);
    }
}