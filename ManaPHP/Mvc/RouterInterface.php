<?php

namespace ManaPHP\Mvc {

    /**
     * ManaPHP\Mvc\RouterInterface initializer
     *
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
         * @param string $host
         * @param bool   $silent
         *
         * @return boolean
         */
        public function handle($uri = null, $host = null, $silent = true);

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
         * @param \ManaPHP\Mvc\Router\GroupInterface $group
         * @param string                             $module
         * @param string                             $path
         *
         * @return  static
         */
        public function mount($group, $module, $path = null);

        /**
         * Set whether router must remove the extra slashes in the handled routes
         *
         * @param boolean $remove
         *
         * @return static
         */
        public function removeExtraSlashes($remove);

        /**
         * Get rewrite info. This info is read from $_GET['_url'] or _SERVER["REQUEST_URI"].
         *
         * @return string
         */
        public function getRewriteUri();

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
    }
}
