<?php
namespace ManaPHP\Security;

/**
 * Interface ManaPHP\Security\CsrfTokenInterface
 *
 * @package csrfToken
 */
interface CsrfTokenInterface
{
    /**
     * @return static
     */
    public function disable();

    /**
     * @return string
     */
    public function get();

    /**
     * @return void
     */
    public function verify();
}