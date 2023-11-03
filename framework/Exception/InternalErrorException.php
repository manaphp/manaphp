<?php
declare(strict_types=1);

namespace ManaPHP\Exception;

use ManaPHP\Exception;

class InternalErrorException extends Exception
{
    public function __construct(string $message = '内部错误，请稍后重试')
    {
        parent::__construct($message);

        $this->json = [
            'code'    => 500,
            'msg' => $this->getMessage()
        ];
    }
}