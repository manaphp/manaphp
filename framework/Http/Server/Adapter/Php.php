<?php
declare(strict_types=1);

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;

/**
 * @property-read \ManaPHP\Http\RouterInterface $router
 */
class Php extends Fpm
{
    protected array $mime_types;
    protected array $root_files;
    protected string $doc_root;

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

            $this->doc_root = $this->alias->resolve('@public');

            $this->root_files = $this->getRootFiles();
            $this->mime_types = $this->getMimeTypes();
        }
    }

    protected function getRootFiles(): array
    {
        $files = [];
        foreach (glob($this->doc_root . '/*') as $file) {
            $file = basename($file);
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                continue;
            }

            $files[] = basename($file);
        }

        return $files;
    }

    protected function getMimeTypes(): array
    {
        $mime_types = [];
        foreach (file(__DIR__ . '/../mime.types', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (!str_contains($line, ';')) {
                continue;
            }

            $line = trim($line);
            $line = trim($line, ';');

            $parts = preg_split('#\s+#', $line, -1, PREG_SPLIT_NO_EMPTY);
            if (count($parts) < 2) {
                continue;
            }

            foreach ($parts as $k => $part) {
                if ($k !== 0) {
                    $mime_types[$part] = $parts[0];
                }
            }
        }

        return $mime_types;
    }

    protected function isStaticFile(): false|string
    {
        $uri = $this->request->getServer('REQUEST_URI');
        $file = ($pos = strpos($uri, '?')) === false ? substr($uri, 1) : substr($uri, 1, $pos - 1);

        if ($file === 'favicon.ico') {
            return '/favicon.ico';
        } elseif (in_array($file, $this->root_files, true)) {
            return $file;
        } elseif (($pos = strpos($file, '/')) === false) {
            return false;
        } else {
            $level1 = substr($file, 0, $pos);
            return in_array($level1, $this->root_files, true) ? $file : false;
        }
    }

    public function start(): void
    {
        $this->prepareGlobals();

        if ($file = $this->isStaticFile()) {
            $file = "$this->doc_root/$file";
            if ((DIRECTORY_SEPARATOR === '/' ? realpath($file) : str_replace('\\', '/', realpath($file))) === $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mime_type = $this->mime_types[$ext] ?? 'application/octet-stream';
                header('Content-Type: ' . $mime_type);
                readfile($file);
            } else {
                header('HTTP/1.1 404 Not Found');
            }
        } else {
            $this->fireEvent('httpServer:start');

            $this->httpHandler->handle();
        }
    }
}