<?php

namespace ManaPHP;

/**
 * Interface ManaPHP\RouterInterface
 *
 * @package router
 */
interface RouterInterface
{
    /**
     * @param string $prefix
     *
     * @return static
     */
    public function setPrefix($prefix);

    /**
     * @return string
     */
    public function getPrefix();

    /**
     * @param array $areas
     *
     * @return static
     */
    public function setAreas($areas);

    /**
     * @return array
     */
    public function getAreas();

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
     * @return \ManaPHP\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $httpMethod = null);

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addGet($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addPost($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addPut($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addPatch($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null);

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addOptions($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null);

    /**
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Router\RouteInterface
     */
    public function addRest($pattern, $paths = null);

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param string $uri
     * @param string $method
     *
     * @return bool
     */
    public function match($uri = null, $method = null);

    /**
     * Get rewrite info. This info is read from $_GET['_url'] or _SERVER["REQUEST_URI"].
     *
     * @param string $uri
     *
     * @return string
     */
    public function getRewriteUri($uri = null);

    /**
     * Returns processed area name
     *
     * @return string
     */
    public function getArea();

    /**
     * Returns processed controller name
     *
     * @return string
     */
    public function getController();

    /**
     * Returns processed action name
     *
     * @return string
     */
    public function getAction();

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
     * @param array|string $args
     * @param string|bool  $scheme
     *
     * @return string
     */
    public function createUrl($args, $scheme = false);
}