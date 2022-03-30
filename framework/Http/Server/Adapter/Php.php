<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;
use ManaPHP\Http\AbstractServer;

/**
 * @property-read \ManaPHP\Http\Server\Adapter\Native\SenderInterface     $sender
 * @property-read \ManaPHP\Http\RouterInterface                           $router
 * @property-read \ManaPHP\Http\Server\Adapter\Php\StaticHandlerInterface $staticHandler
 * @property-read \ManaPHP\AliasInterface                                 $alias
 */
class Php extends AbstractServer
{
    public function __construct(array $options = [])
    {
        parent::__construct($options);

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

        if (PHP_SAPI === 'cli') {
            if (DIRECTORY_SEPARATOR === '\\') {
                shell_exec("explorer.exe http://127.0.0.1:$this->port" . ($this->router->getPrefix() ?: '/'));
            }
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $index = @get_included_files()[0];
            $cmd = "php -S $this->host:$this->port -t $public_dir  $index";
            console_log('info', $cmd);
            $prefix = $this->router->getPrefix();
            console_log('info', "http://127.0.0.1:$this->port" . ($prefix ?: '/'));
            shell_exec($cmd);
            exit(0);
        } else {
            $_SERVER['SERVER_ADDR'] = $local_ip;
            $_SERVER['SERVER_PORT'] = $this->port;
            $_SERVER['REQUEST_SCHEME'] = 'http';
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