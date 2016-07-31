<?php
namespace ManaPHP\Security;

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
     * @throws \ManaPHP\Security\CsrfToken\Exception
     */
    public function verify();
}