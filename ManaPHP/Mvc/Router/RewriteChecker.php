<?php
namespace ManaPHP\Mvc\Router;

use ManaPHP\Mvc\Router\RewriteChecker\Exception as RewriteCheckerException;

/**
 * Class ManaPHP\Mvc\Router\RewriteChecker
 *
 * @package ManaPHP\Mvc\Router
 */
class RewriteChecker
{
    /**
     * RewriteChecker constructor.
     *
     * @throws \ManaPHP\Mvc\Router\RewriteChecker\Exception
     */
    public function __construct()
    {
        if (!isset($_SERVER['MANAPHP_REWRITE_ON'])) {
            if (PHP_SAPI === 'apache2handler') {
                if (!in_array('mod_rewrite', apache_get_modules(), true)) {
                    throw new RewriteCheckerException('please install Apache mod_rewrite module'/**m07e2546d90a3b8271*/);
                }
            }
        }
    }
}