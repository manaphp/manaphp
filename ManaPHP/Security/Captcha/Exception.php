<?php
namespace ManaPHP\Security\Captcha {

    class Exception extends \ManaPHP\Security\Exception
    {
        const CODE_UNKNOWN = 0;
        const CODE_GENERATE_TOO_FREQUENCY = 1;
        const CODE_NOT_EXIST = 2;
        const CODE_VERIFY_TOO_FREQUENCY = 3;
        const CODE_EXPIRE=5;
        const CODE_NOT_MATCH = 4;
    }
}