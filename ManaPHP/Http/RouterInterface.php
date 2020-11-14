<?php

namespace ManaPHP\Http;

/**
 * Interface ManaPHP\Http\RouterInterface
 *
 * @package router
 */
interface RouterInterface
{
    /**
     * @return bool
     */
    public function isCaseSensitive();

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
    public function setAreas($areas = null);

    /**
     * @return array
     */
    public function getAreas();

    /**
     * Adds a route to the router on any HTTP method
     *
     * @param string       $pattern
     * @param string|array $paths
     * @param string       $method
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function add($pattern, $paths = null, $method = null);

    /**
     * Adds a route to the router that only match if the HTTP method is GET
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addGet($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is POST
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addPost($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is PUT
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addPut($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is PATCH
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addPatch($pattern, $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is DELETE
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addDelete($pattern, $paths = null);

    /**
     * Add a route to the router that only match if the HTTP method is OPTIONS
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addOptions($pattern = '{all:.*}', $paths = null);

    /**
     * Adds a route to the router that only match if the HTTP method is HEAD
     *
     * @param string       $pattern
     * @param string|array $paths
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addHead($pattern, $paths = null);

    /**
     * @param string $pattern
     * @param string $controller
     *
     * @return \ManaPHP\Http\Router\RouteInterface
     */
    public function addRest($pattern, $controller = null);

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param string $uri
     * @param string $method
     *
     * @return \ManaPHP\Http\RouterContext|false
     */
    public function match($uri = null, $method = null);

    /**
     * Handles routing information received from the rewrite engine
     *
     * @param string $uri
     * @param string $method
     *
     * @return mixed
     * @throws \ManaPHP\Exception\AbortException
     */
    public function dispatch($uri = null, $method = null);

    /**
     * Get rewrite info.
     *
     * @return string
     */
    public function getRewriteUri();

    /**
     * Returns processed area name
     *
     * @return string
     */
    public function getArea();

    /**
     * @param string $area
     *
     * @return static
     */
    public function setArea($area);

    /**
     * Returns processed controller name
     *
     * @return string
     */
    public function getController();

    /**
     * @param string $controller
     *
     * @return static
     */
    public function setController($controller);

    /**
     * Returns processed action name
     *
     * @return string
     */
    public function getAction();

    /**
     * @param string $action
     *
     * @return static
     */
    public function setAction($action);

    /**
     * Returns processed extra params
     *
     * @return array
     */
    public function getParams();

    /**
     * @param array $params
     *
     * @return static
     */
    public function setParams($params);

    /**
     * Check if the router matches any of the defined routes
     *
     * @return bool
     */
    public function wasMatched();

    /**
     * @param bool $matched
     *
     * @return static
     */
    public function setMatched($matched);

    /**
     * @param array|string $args
     * @param string|bool  $scheme
     *
     * @return string
     */
    public function createUrl($args, $scheme = false);
}
