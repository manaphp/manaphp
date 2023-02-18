<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;

/**
 * @property-read \ManaPHP\Http\Server\Adapter\Native\SenderInterface $sender
 * @property-read \ManaPHP\Http\Server\StaticHandlerInterface         $staticHandler
 * @property-read \ManaPHP\AliasInterface                             $alias
 */
class Php extends AbstractServer
{
    public function __construct(string $host = '0.0.0.0', int $port = 9501, array $settings = [])
    {
        parent::__construct($host, $port);

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
            if (($worker_num = $settings['worker_num'] ?? 1) > 1) {
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

        $this->staticHandler->start($_SERVER['DOCUMENT_ROOT'], $this->router->getPrefix());

        if ($this->staticHandler->isStaticFile()) {
            $this->staticHandler->send();
        } else {
            $this->fireEvent('httpServer:start');

            $this->httpHandler->handle();
        }
    }

    public function send(): void
    {
        $this->sender->send();
    }
}