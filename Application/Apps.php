<?php
namespace Application;

class Apps
{
    /**
     * @var \ManaPHP\Loader
     */
    protected $_loader;

    /**
     * Group constructor.
     *
     * @param \ManaPHP\Loader $loader
     */
    public function __construct($loader)
    {
        $this->_loader = $loader;
        $this->_loader->registerNamespaces(['Application' => __DIR__]);
    }

    /**
     * @param array $apps
     *
     * @return string|bool
     */
    public function select($apps)
    {
        if (isset($_GET['_url'])) {
            $uri = $_GET['_url'];
        } elseif (isset($_SERVER['PATH_INFO'])) {
            $uri = $_SERVER['PATH_INFO'];
        } else {
            $uri = '/';
        }

        foreach (array_reverse($apps, true) as $app => $path) {
            if ($path[0] !== '/') {
                $host = $_SERVER['HTTP_HOST'];

                if (strpos($path, $host) !== 0) {
                    continue;
                }
                $path = $path === $host ? '/' : substr($path, strlen($host));
            }

            if (strpos($uri, $path) !== 0) {
                continue;
            }

            return $app;
        }

        return false;
    }

    public function main()
    {
        $apps = ['Home' => '/', 'Admin' => '/admin', 'Api' => '/api'];
        $app = $this->select($apps);
        if ($app) {
            /**
             * @var \ManaPHP\Mvc\Application $appInstance
             */
            $appClassName = __NAMESPACE__ . '\\' . ucfirst($app) . '\\Application';
            $appInstance = new  $appClassName($this->_loader);
            $appInstance->router->setPrefix($apps[$app]);
            $appInstance->main();
        } else {
            header('HTTP/1.1 404 Not Found');
            exit('<h1>no app available</h1>');
        }
    }
}