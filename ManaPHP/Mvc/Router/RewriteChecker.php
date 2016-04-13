<?php
namespace ManaPHP\Mvc\Router {

    use ManaPHP\Mvc\Router\RewriteChecker\Exception;

    class RewriteChecker
    {
        public function __construct()
        {
            if (!isset($_SERVER['MANAPHP_REWRITE_ON'])) {
                if (PHP_SAPI === 'apache2handler') {
                    if (!in_array('mod_rewrite', apache_get_modules(), true)) {
                        throw new Exception('please install Apache mod_rewrite module.');
                    }
                }
            }
        }
    }
}