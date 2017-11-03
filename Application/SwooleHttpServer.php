<?php
namespace Application;

class SwooleHttpServer extends \ManaPHP\Swoole\HttpServer
{
    protected function _beforeRequest()
    {
        $this->debugger->start();

        xdebug_start_trace('/home/mark/manaphp/data/traces/' . date('Ymd_His_') . mt_rand(1000, 9999) . '.trace');
    }

    protected function _afterRequest()
    {
        xdebug_stop_trace();
    }
	
    /**
     * @param \swoole_http_server $swoole
     */
    protected function _prepareSwoole($swoole)
    {
        echo '   current_time: ', date('Y-m-d H:i:s'), PHP_EOL;
        echo 'swoole lisen on: ', $this->_listen, PHP_EOL;

        $swoole->set(['worker_num' => 1, 'enable_static_handler' => true, 'document_root' => dirname(get_included_files()[0])]);

    }
}
