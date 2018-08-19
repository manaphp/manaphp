<?php
namespace App;

use ManaPHP\Swoole\Http\Server;

class Swoole extends Server
{
    protected function _beforeRequest()
    {
        parent::_beforeRequest();
        //  $this->debugger->start();

        //xdebug_start_trace('/home/mark/manaphp/data/traces/' . date('Ymd_His_') . mt_rand(1000, 9999) . '.trace');
    }

    protected function _afterRequest()
    {
        parent::_afterRequest();

        //   xdebug_stop_trace();
    }

    public function authenticate()
    {

    }

    protected function _prepareSwoole()
    {
        echo '   current_time: ', date('Y-m-d H:i:s'), PHP_EOL;
        echo 'swoole listen on: ', $this->_listen, PHP_EOL;

        $this->_swoole->set(['worker_num' => 3,'dispatch_mode'=>3]);
    }
}
