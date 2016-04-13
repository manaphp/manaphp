<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 2015/11/15
 * Time: 17:02
 */

namespace ManaPHP\Mvc\Router {

    /**
     * ManaPHP\Mvc\Router\GroupInterface
     *
     * PHP_NOTE:
     *      String paths(:: separated) formatted has been removed.
     *      for example "App::Blog::Add"
     *
     *<code>
     * $router = new \ManaPHP\Mvc\Router();
     *
     * //Create a group with a common module and controller
     * $blog = new Group(array(
     *    'module' => 'blog',
     *    'controller' => 'index'
     * ));
     *
     * //All the routes start with /blog
     * $blog->setPrefix('/blog');
     *
     * //Add a route to the group
     * $blog->add('/save', array(
     *    'action' => 'save'
     * ));
     *
     * //Add another route to the group
     * $blog->add('/edit/{id}', array(
     *    'action' => 'edit'
     * ));
     *
     * //This route maps to a controller different than the default
     * $blog->add('/blog', array(
     *    'controller' => 'about',
     *    'action' => 'index'
     * ));
     *
     * //Add the group to the router
     * $router->mount($blog);
     *</code>
     *
     */
    interface GroupInterface
    {
        /**
         * Returns the routes added to the group
         *
         * @return \ManaPHP\Mvc\Router\RouteInterface[]
         */
        public function getRoutes();

        /**
         * Adds a route to the router on any HTTP method
         *
         *<code>
         * router->add('/about', 'About::index');
         *</code>
         *
         * @param string       $pattern
         * @param string|array $paths
         * @param array        $httpMethods
         *
         * @return \ManaPHP\Mvc\Router\RouteInterface
         */
        public function add($pattern, $paths = null, $httpMethods = null);

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
    }
}