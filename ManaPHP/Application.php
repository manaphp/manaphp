<?php
namespace ManaPHP;

use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Application
 *
 * @package application
 *
 * @property \ManaPHP\Loader            $loader
 * @property \ManaPHP\DebuggerInterface $debugger
 */
abstract class Application extends Component implements ApplicationInterface
{
    /**
     * @var array
     */
    protected $_modules;

    /**
     * Application constructor.
     *
     * @param \ManaPHP\Loader      $loader
     * @param \ManaPHP\DiInterface $dependencyInjector
     */
    public function __construct($loader, $dependencyInjector = null)
    {
        $this->_dependencyInjector = $dependencyInjector ?: new FactoryDefault();

        $this->_dependencyInjector->setShared('loader', $loader);
        $this->_dependencyInjector->setShared('application', $this);

        $class = str_replace('\\', '/', get_called_class());
        foreach (get_included_files() as $file) {
            if (DIRECTORY_SEPARATOR === '\\') {
                $file = str_replace('\\', '/', $file);
            }

            if (strpos($file, $class . '.php') !== false) {
                $app_dir = dirname($file);
                $app_ns = basename($app_dir);
                $this->alias->set('@app', $app_dir);
                $this->alias->set('@ns.app', $app_ns);
                $this->alias->set('@data', dirname($app_dir) . '/Data');
                $this->loader->registerNamespaces([$app_ns => $app_dir]);
                break;
            }
        }
    }

    /**
     * @return array
     */
    public function getModules()
    {
        if ($this->_modules === null) {
            $modules = [];
            foreach (glob($this->alias->resolve('@app/*'), GLOB_ONLYDIR) as $dir) {
                if (is_file($dir . '/Module.php')) {
                    $modules[] = basename($dir);
                }
            }

            $this->_modules = $modules;
        }

        return $this->_modules;
    }
}