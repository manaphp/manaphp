<?php

namespace App\Services;

use ManaPHP\Rpc\Client\Service;

class TimeService extends Service
{
    public function current()
    {
        return $this->_rpcCall(__METHOD__, func_get_args());
    }
}