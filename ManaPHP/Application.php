<?php
namespace ManaPHP;

use ManaPHP\Application\AbortException;
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
                if (is_dir($dir . '/Controllers')) {
                    $module = basename($dir);
                    if ($module !== 'Cli') {
                        $modules[] = $module;
                    }
                }
            }

            $this->_modules = $modules;
        }

        return $this->_modules;
    }

    /**
     * @param int    $code
     * @param string $message
     *
     * @throws \ManaPHP\Application\AbortException
     */
    public function abort($code, $message)
    {
        throw new AbortException($message, $code);
    }

    public function registerServices()
    {
        $configureClass = $this->alias->resolve('@ns.app\\Configure');
        if (class_exists($configureClass)) {
            $this->_dependencyInjector->setShared('configure', new $configureClass);
        }

        $configure = $this->configure;

        if (PHP_SAPI !== 'cli') {
            foreach (isset($configure->modules) ? $configure->modules : ['Home' => '/'] as $module => $path) {
                $this->_dependencyInjector->router->mount($module, $path);
            }
        }

        if (isset($configure->redis)) {
            $c = (array)$configure->redis;
            foreach (isset($c['host']) ? ['redis' => $c] : $c as $service => $config) {
                $c += ['port' => 6379, 'timeout' => 0.0];
                $this->_dependencyInjector->setShared($service, function () use ($config) {
                    $redis = new \Redis();
                    $redis->connect($config['host'], $config['port'], $config['timeout']);

                    return $redis;
                });
            }
        }

        if (isset($configure->db)) {
            $c = (array)$configure->db;
            foreach (isset($c['host']) ? ['db' => $c] : $c as $service => $config) {
                $adapter = isset($config['adapter']) ? $config['adapter'] : 'ManaPHP\Db\Adapter\Mysql';
                unset($config['adapter']);
                $this->_dependencyInjector->setShared($service, [$adapter, [$config]]);
            }
        }
    }
}