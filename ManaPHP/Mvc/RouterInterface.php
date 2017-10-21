<?php

namespace ManaPHP\Mvc;

/**
 * Interface ManaPHP\Mvc\RouterInterface
 *
 * @package router
 */
interface RouterInterface
{
    /**
     * Handles routing information received from the rewrite engine
     *
     * <code>
     *
     *  $router->handle();  //==>$router->handle($_GET['_url'],$_SERVER['HTTP_HOST']);
     *
     *  $router->handle('/blog');   //==>$router->handle('/blog',$_SERVER['HTTP_HOST']);
     *
     * $router->handle('/blog','www.manaphp.com');
     *
     * </code>
     * @param string $uri
     * @param string $method
     * @param string $host
     *
     * @return bool
     */
    public function handle($uri = null, $method = null, $host = null);

    /**
     * Mounts a group of routes in the router
     *
     * <code>
     *  $group=new \ManaPHP\Mvc\Router\Group();
     *
     *  $group->addGet('/blog','blog::list');
     *  $group->addGet('/blog/{id:\d+}','blog::detail')
     *
     *  $router=new \ManaPHP\Mvc\Router();
     *  $router->mount($group,'home');
     * </code>
     *
     * @param string $module
     * @param string $path
     *
     * @return static
     */
    public function mount($module, $path = null);

    /**
     * @return array
     */
    public function getMounted();

    /**
     * Get rewrite info. This info is read from $_GET['_url'] or _SERVER["REQUEST_URI"].
     *
     * @param string $uri
     *
     * @return string
     */
    public function getRewriteUri($uri = null);

    /**
     * Returns processed module name
     *
     * @return string
     */
    public function getModuleName();

    /**
     * Returns processed controller name
     *
     * @return string
     */
    public function getControllerName();

    /**
     * Returns processed action name
     *
     * @return string
     */
    public function getActionName();

    /**
     * Returns processed extra params
     *
     * @return array
     */
    public function getParams();

    /**
     * Check if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched();

    /**
     * @param string $path
     * @param array  $params
     *
     * @return string
     */
    public function createActionUrl($path, $params = []);
}