<?php
namespace ManaPHP\Rpc\Server\Adapter;

/**
 * Class Php
 * @package ManaPHP\Rpc\Server\Adapter
 *
 * @property-read \ManaPHP\RouterInterface $router
 */
class Php extends Fpm
{
    /**
     * @var string
     */
    protected $_host = '0.0.0.0';

    /**
     * @var int
     */
    protected $_port = 9505;

    /**
     * Fpm constructor.
     *
     * @param array $options
     */
    public function __construct($options = null)
    {
        if (isset($options['host'])) {
            $this->_host = $options['host'];
            unset($options['host']);
        }

        if (isset($options['port'])) {
            $this->_port = (int)$options['port'];
            unset($options['port']);
        }

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
        }
    }

    public function log($level, $message)
    {
        echo sprintf('[%s][%s]: ', date('c'), $level), $message, PHP_EOL;
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
}