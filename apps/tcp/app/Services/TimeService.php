<?php

namespace App\Services;

use ManaPHP\Rpc\Client\Service;

class TimeService extends Service
{
    public function current()
    {
        return $this->rpcCall(__METHOD__, func_get_args());
    }
}