<?php
namespace Application;

class SwooleHttpServer extends \ManaPHP\Swoole\HttpServer
{
    /**
     * @param \swoole_http_server $swoole
     */
    protected function _prepareSwoole($swoole)
    {
        $swoole->set(['worker_num' => 1, 'enable_static_handler' => true, 'document_root' => dirname(get_included_files()[0])]);
    }
}
