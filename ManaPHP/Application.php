<?php

namespace ManaPHP;

use ManaPHP\Application\AbortException;
use ManaPHP\Application\Exception as ApplicationException;
use ManaPHP\Di\FactoryDefault;

/**
 * Class ManaPHP\Application
 *
 * @package application
 *
 * @property \ManaPHP\Loader              $loader
 * @property \ManaPHP\DebuggerInterface   $debugger
 * @property \ManaPHP\FilesystemInterface $filesystem
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

        $app_dir = $this->getAppPath();
        $app_ns = basename($app_dir);

        $this->loader->registerNamespaces([$app_ns => $app_dir]);
        $this->alias->set('@root', dirname($app_dir));
        $this->alias->set('@data', '@root/data');
        $this->alias->set('@app', $app_dir);
        $this->alias->set('@ns.app', $app_ns);

        $web = '';
        if (isset($_SERVER['SCRIPT_NAME']) && ($pos = strrpos($_SERVER['SCRIPT_NAME'], '/')) > 0) {
            $web = substr($_SERVER['SCRIPT_NAME'], 0, $pos);
            if (substr_compare($web, '/public', -7) === 0) {
                $web = substr($web, 0, -7);
            }
        }
        $this->alias->set('@web', $web);
    }

    /**
     * @return string
     */
    public function getAppPath()
    {
        $className = str_replace('\\', '/', get_called_class());
        foreach (get_included_files() as $file) {
            if (DIRECTORY_SEPARATOR === '\\') {
                $file = str_replace('\\', '/', $file);
            }

            if (strpos($file, $className . '.php') !== false) {
                return dirname($file);
            }
        }

        $dir = dirname(get_included_files()[0]);
        for ($i = 0; $i < 2; $i++) {
            if (is_dir($dir . '/Application')) {
                return $dir . '/Application';
            }
            $dir = dirname($dir);
        }

        return null;
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
        $configureClass = $this->alias->resolveNS('@ns.app\\Configure');
        if (class_exists($configureClass)) {
            $this->_dependencyInjector->setShared('configure', new $configureClass);
        }

        $configure = $this->configure;

        date_default_timezone_set($configure->timezone);

        if (!$this->alias->has('@cli')) {
            $this->_dependencyInjector->router->mount(isset($configure->modules) ? $configure->modules : ['Home' => '/']);
        }

        if (isset($configure->redis)) {
            $this->_dependencyInjector->setShared('redis', ['ManaPHP\Redis', [$configure->redis]]);
        }

        if (isset($configure->db)) {
            if (is_string($configure->db)) {
                $scheme = parse_url($configure->db, PHP_URL_SCHEME);
                if ($scheme === false) {
                    throw new ApplicationException('`:db` db config is invalid', ['db' => $configure->db]);
                }

                $adapter = 'ManaPHP\Db\Adapter\\' . ucfirst($scheme);
                $config = $configure->db;
            } else {
                $config = (array)$configure->db;
                $adapter = isset($config['adapter']) ? $config['adapter'] : 'ManaPHP\Db\Adapter\Mysql';
                unset($config['adapter']);
            }
            $this->_dependencyInjector->setShared('db', [$adapter, [$config]]);
        }

        if (isset($configure->mongodb)) {
            $this->_dependencyInjector->setShared('mongodb', new Mongodb($configure->mongodb));
        }
    }
}