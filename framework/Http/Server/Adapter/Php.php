<?php

declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Alias\Path;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Server\Adapter\Native\SenderInterface;
use ManaPHP\Http\Server\Event\ServerReady;
use ManaPHP\Http\Server\StaticHandlerInterface;
use function console_log;
use function file_get_contents;
use function get_included_files;
use function header;
use function putenv;
use function shell_exec;

class Php extends AbstractServer
{
    #[Autowired] protected SenderInterface $sender;
    #[Autowired] protected StaticHandlerInterface $staticHandler;

    #[Autowired] protected array $settings = [];

    public function __construct()
    {
        $argv = $GLOBALS['argv'] ?? [];
        foreach ($argv as $k => $v) {
            if ($v === '--port' || $v === '-p') {
                if (isset($argv[$k + 1])) {
                    $this->port = ($argv[$k + 1]);
                    break;
                }
            }
        }

        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['SERVER_ADDR'] = $this->host === '0.0.0.0' ? Ip::local() : $this->host;
        $_SERVER['SERVER_PORT'] = $this->port;
    }

    protected function prepareGlobals(): void
    {
        $rawBody = file_get_contents('php://input');
        $this->request->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_FILES);
    }

    public function start(): void
    {
        if (PHP_SAPI === 'cli') {
            if (($worker_num = $this->settings['worker_num'] ?? 4) > 1) {
                putenv("PHP_CLI_SERVER_WORKERS=$worker_num");
            }

            $public_dir = Path::resolve('@public');

            $index = @get_included_files()[0];
            $cmd = PHP_BINARY . " -S $this->host:$this->port -t $public_dir  $index";
            console_log('info', $cmd);
            $prefix = $this->router->getPrefix();
            console_log('info', "http://127.0.0.1:$this->port" . ($prefix ?: '/'));
            shell_exec($cmd);
            exit(0);
        }

        $this->prepareGlobals();

        $this->bootstrap();

        $uri = $_SERVER['REQUEST_URI'];
        if ($this->staticHandler->isFile($uri)) {
            if (($file = $this->staticHandler->getFile($uri)) !== null) {
                header('Content-Type: ' . $this->staticHandler->getMimeType($file));
                readfile($file);
            } else {
                header('HTTP/1.1 404 Not Found');
            }
        } else {
            $this->eventDispatcher->dispatch(new ServerReady(null, $this->host, $this->port));

            $this->requestHandler->handle();
        }
    }

    public function sendHeaders(): void
    {
        $this->sender->sendHeaders();
    }

    public function sendBody(): void
    {
        $this->sender->sendBody();
    }
}
