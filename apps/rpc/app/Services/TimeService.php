<?php

namespace App\Services;

use ManaPHP\Rpc\Client\Service;

class TimeService extends Service
{
    /**
     * @return int
     */
    public function current()
    {
        return $this->invoke(__METHOD__, func_get_args());
    }

    /**
     * @param int $second
     *
     * @return int
     */
    public function after($second = 0)
    {
        return $this->invoke(__METHOD__, func_get_args());
    }
}