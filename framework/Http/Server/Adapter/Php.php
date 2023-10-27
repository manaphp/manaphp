<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\AliasInterface;
use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;
use ManaPHP\Http\Server\Adapter\Native\SenderInterface;
use ManaPHP\Http\Server\Event\ServerReady;
use ManaPHP\Http\Server\StaticHandlerInterface;

class Php extends AbstractServer
{
    #[Autowired] protected SenderInterface $sender;
    #[Autowired] protected StaticHandlerInterface $staticHandler;
    #[Autowired] protected AliasInterface $alias;

    #[Autowired] protected array $settings = [];

    /** @noinspection PhpTypedPropertyMightBeUninitializedInspection */
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

        $public_dir = $this->alias->resolve('@public');
        $local_ip = $this->host === '0.0.0.0' ? Ip::local() : $this->host;

        $_SERVER['REQUEST_SCHEME'] = 'http';

        if (PHP_SAPI === 'cli') {
            if (($worker_num = $this->settings['worker_num'] ?? 1) > 1) {
                putenv("PHP_CLI_SERVER_WORKERS=$worker_num");
            }

            $e = extension_loaded('yasd') && ini_get('opcache.optimization_level') === '0' ? '-e' : '';
            $index = @get_included_files()[0];
            $cmd = PHP_BINARY . " $e -S $this->host:$this->port -t $public_dir  $index";
            console_log('info', $cmd);
            $prefix = $this->router->getPrefix();
            console_log('info', "http://127.0.0.1:$this->port" . ($prefix ?: '/'));
            shell_exec($cmd);
            exit(0);
        } else {
            $_SERVER['SERVER_ADDR'] = $local_ip;
            $_SERVER['SERVER_PORT'] = $this->port;
        }
    }

    protected function prepareGlobals(): void
    {
        $rawBody = file_get_contents('php://input');
        $this->globals->prepare($_GET, $_POST, $_SERVER, $rawBody, $_COOKIE, $_FILES);
    }

    public function start(): void
    {
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
            $this->eventDispatcher->dispatch(new ServerReady());

            $this->httpHandler->handle();
        }
    }

    public function send(): void
    {
        $this->sender->send();
    }
}