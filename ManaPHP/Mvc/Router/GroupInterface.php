<?php
namespace ManaPHP\Mvc\Router;

/**
 * Interface ManaPHP\Mvc\Router\GroupInterface
 *
 * @package router
 */
interface GroupInterface
{
    /**
     * Adds a route to the router on any HTTP method
     *
     *<code>
     * router->add('/about', 'About::index');
     *</code>
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $httpMethod
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $httpMethod = null);

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addGet($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addPost($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addPut($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addPatch($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null);

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addOptions($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null);

    /**
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Mvc\Router\RouteInterface
     */
    public function addRest($pattern, $paths = null);

    /**
     * @param string $uri
     * @param string $method
     *
     * @return false|array
     */
    public function match($uri, $method = 'GET');
}