<?php
namespace ManaPHP\Security\CsrfToken;

/**
 * Class ManaPHP\Security\CsrfToken\Exception
 *
 * @package csrfToken
 */
class Exception extends \ManaPHP\Security\Exception
{
    public function getStatusCode()
    {
        return 400;
    }
}