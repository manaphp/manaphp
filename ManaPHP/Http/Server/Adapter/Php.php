<?php
namespace ManaPHP\Http\Server\Adapter;

use ManaPHP\Http\Server;

/**
 * Class Php
 * @package ManaPHP\Http\Server\Adapter
 * @property-read \ManaPHP\RouterInterface $router
 */
class Php extends Server
{
    /**
     * Fpm constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $public_dir = $this->alias->resolve('@public');
        $local_ip = $this->_getLocalIp();

        if (PHP_SAPI === 'cli') {
            if (DIRECTORY_SEPARATOR === '\\') {
                shell_exec("explorer.exe http://127.0.0.1:$this->_port" . ($this->router->getPrefix() ?: '/'));
            }
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $index = @get_included_files()[0];
            $cmd = "php -S $this->_host:$this->_port -t $public_dir  $index";
            $this->log('info', $cmd);
            $this->log('info', "http://$local_ip:$this->_port" . ($this->router->getPrefix() ?: '/'));
            shell_exec($cmd);
            exit(0);
        } else {
            $_SERVER['SERVER_ADDR'] = $local_ip;
            $_SERVER['SERVER_PORT'] = $this->_port;
            $_SERVER['REQUEST_SCHEME'] = 'http';
            $_GET['_url'] = $_REQUEST['_url'] = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            $this->_root_files = $this->_getRootFiles();
            $this->_mime_types = $this->_getMimeTypes();
        }
    }

    /**
     * @return string
     */
    protected function _getLocalIp()
    {
        if ($this->_host === '0.0.0.0') {
            if (function_exists('net_get_interfaces')) {
                $ips = net_get_interfaces();

                if (isset($ips['eth0'])) {
                    $unicast = $ips['eth0'];
                } elseif (isset($ips['ens33'])) {
                    $unicast = $ips['ens33'];
                } else {
                    $unicast = [];
                    foreach ($ips as $name => $ip) {
                        if ($name !== 'lo' && $name !== 'docker' && strpos($name, 'br-') !== 0) {
                            $unicast = $ip;
                            break;
                        }
                    }
                }

                foreach ($unicast as $items) {
                    foreach ($items as $item) {
                        if (isset($item['address'])) {
                            $ip = $item['address'];
                            if (strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
                                return $ip;
                            }
                        }
                    }
                }
                return $this->_host;
            } elseif (DIRECTORY_SEPARATOR === '\\') {
                return '127.0.0.1';
            } else {
                if (!$ips = @exec('hostname --all-ip-addresses')) {
                    return '127.0.0.1';
                }

                $ips = explode(' ', $ips);

                foreach ($ips as $ip) {
                    if (strpos($ip, '172.') === 0 && preg_match('#\.1$#', $ip)) {
                        continue;
                    }
                    return $ip;
                }
                return $ips[0];
            }
        } else {
            return $this->_host;
        }
    }

    protected function _prepareGlobals()
    {
        if (!$_POST && isset($_SERVER['REQUEST_METHOD']) && !in_array($_SERVER['REQUEST_METHOD'], ['GET', 'OPTIONS'], true)) {
            $data = file_get_contents('php://input');

            if (isset($_SERVER['CONTENT_TYPE'])
                && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $_POST = json_decode($data, true, 16);
            } else {
                parse_str($data, $_POST);
            }

            if (is_array($_POST)) {
                /** @noinspection AdditionOperationOnArraysInspection */
                $_REQUEST = $_POST + $_GET;
            } else {
                $_POST = [];
            }
        }

        $this->request->setRequestId($_SERVER['HTTP_X_REQUEST_ID'] ?? null);

        $globals = $this->request->getGlobals();

        $globals->_GET = $_GET;
        $globals->_POST = $_POST;
        $globals->_REQUEST = $_REQUEST;
        $globals->_FILES = $_FILES;
        $globals->_COOKIE = $_COOKIE;
        $globals->_SERVER = $_SERVER;

        if (!$this->_compatible_globals) {
            unset($_GET, $_POST, $_REQUEST, $_FILES, $_COOKIE);
            foreach ($_SERVER as $k => $v) {
                if (strpos('DOCUMENT_ROOT,SERVER_SOFTWARE,SCRIPT_NAME,SCRIPT_FILENAME', $k) === false) {
                    unset($_SERVER[$k]);
                }
            }
        }

        $GLOBALS['globals'] = $globals;
    }

    /**
     * @param \ManaPHP\Http\Server\HandlerInterface $handler
     *
     * @return static
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
        
        return $this;
    }

    /**
     * @param \ManaPHP\Http\ResponseInterface $response
     */
    public function send($response)
    {
        $response->send();
    }
}