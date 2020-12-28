<?php

namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Helper\Ip;

/**
 * @property-read \ManaPHP\Http\RouterInterface $router
 */
class Php extends Fpm
{
    /**
     * @var array
     */
    protected $_mime_types;

    /**
     * @var array
     */
    protected $_root_files;

    /**
     * @var string
     */
    protected $_doc_root;

    /**
     * @param array $options
     */
    public function __construct($options = [])
    {
        $public_dir = $this->alias->resolve('@public');
        $local_ip = $this->_host === '0.0.0.0' ? Ip::local() : $this->_host;

        if (PHP_SAPI === 'cli') {
            if (DIRECTORY_SEPARATOR === '\\') {
                shell_exec("explorer.exe http://127.0.0.1:$this->_port" . ($this->router->getPrefix() ?: '/'));
            }
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $index = @get_included_files()[0];
            $cmd = "php -S $this->_host:$this->_port -t $public_dir  $index";
            console_log('info', $cmd);
            console_log('info', "http://$local_ip:$this->_port" . ($this->router->getPrefix() ?: '/'));
            shell_exec($cmd);
            exit(0);
        } else {
            $_SERVER['SERVER_ADDR'] = $local_ip;
            $_SERVER['SERVER_PORT'] = $this->_port;
            $_SERVER['REQUEST_SCHEME'] = 'http';

            $this->_doc_root = $this->alias->resolve('@public');

            $this->_root_files = $this->_getRootFiles();
            $this->_mime_types = $this->_getMimeTypes();
        }

        parent::__construct($options);
    }

    /**
     * @return string[]
     */
    protected function _getRootFiles()
    {
        $files = [];
        foreach (glob($this->_doc_root . '/*') as $file) {
            $file = basename($file);
            if ($file[0] === '.' || pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                continue;
            }

            $files[] = basename($file);
        }

        return $files;
    }

    /**
     * @return string[]
     */
    protected function _getMimeTypes()
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

    /**
     * @return false|string
     */
    protected function _isStaticFile()
    {
        $uri = $this->request->getServer('REQUEST_URI');
        $file = ($pos = strpos($uri, '?')) === false ? substr($uri, 1) : substr($uri, 1, $pos - 1);

        if ($file === 'favicon.ico') {
            return '/favicon.ico';
        } elseif (in_array($file, $this->_root_files, true)) {
            return $file;
        } elseif (($pos = strpos($file, '/')) === false) {
            return false;
        } else {
            $level1 = substr($file, 0, $pos);
            return in_array($level1, $this->_root_files, true) ? $file : false;
        }
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return void
     */
    public function start($handler)
    {
        $this->_prepareGlobals();

        if ($file = $this->_isStaticFile()) {
            $file = "$this->_doc_root/$file";
            if ((DIRECTORY_SEPARATOR === '/' ? realpath($file) : str_replace('\\', '/', realpath($file))) === $file) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                $mime_type = $this->_mime_types[$ext] ?? 'application/octet-stream';
                header('Content-Type: ' . $mime_type);
                readfile($file);
            } else {
                header('HTTP/1.1 404 Not Found');
            }
        } else {
            $handler->handle();
        }
    }
}