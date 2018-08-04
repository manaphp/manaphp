<?php
namespace ManaPHP\Net;

use ManaPHP\Component;
use ManaPHP\Net\Connectivity\Exception as ConnectivityException;

class Connectivity extends Component implements ConnectivityInterface
{
    /**
     * @var array
     */
    protected $_scheme2Port = [
        'http' => 80,
        'https' => 443,
        'mysql' => 3306,
        'amqp' => 5672,
        'redis' => 6379,
        'mongodb' => 27017
    ];

    /**
     * @param string $url
     * @param float  $time
     *
     * @return bool
     *
     * @throws \ManaPHP\Net\Connectivity\Exception
     */
    public function test($url, $time = 0.1)
    {
        if (strpos($url, ',') !== false) {
            $scheme = strpos($url, '://') !== false ? parse_url($url, PHP_URL_SCHEME) : null;
            foreach (explode(',', $url) as $u) {
                $u = trim($u);

                if ($scheme !== null && strpos($u, '://') === false) {
                    $u = $scheme . '://' . $u;
                }
                if ($this->test($u)) {
                    return true;
                }
            }

            return false;
        }

        if (strpos($url, '://') === false) {
            $parts = explode(':', $url, 2);
            if (count($parts) !== 2) {
                throw new ConnectivityException(['`:url` url is invalid.', 'url' => $url]);
            }
            $host = $parts[0];
            $port = $parts[1];
        } else {
            $parts = parse_url($url);
            if (!isset($parts['host'])) {
                throw new ConnectivityException(['`:url` url is not contains host part.', 'url' => $url]);
            }
            $host = $parts['host'];
            if (isset($parts['port'])) {
                $port = $parts['port'];
            } else {
                $scheme = $parts['scheme'];
                $port = getservbyname($scheme, 'tcp');
                if ($port === false && isset($this->_scheme2Port[$scheme])) {
                    $port = $this->_scheme2Port[$scheme];
                }

                if ($port === false) {
                    throw new ConnectivityException(['`:scheme` scheme of `:url` url is unknown.', 'scheme' => $scheme, 'url' => $url]);
                }
            }
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($socket === false) {
            throw new ConnectivityException(['test `:url` url failed: `:socket_error`', 'url' => $url, '']);
        }
        $r = @socket_connect($socket, $host, $port);
        socket_close($socket);
        return $r;
    }

    /**
     * @param array $urls
     * @param float $time
     *
     * @return bool
     * @throws \ManaPHP\Net\Connectivity\Exception
     */
    public function wait($urls, $time = 0.1)
    {
        $r = [];
        $end_time = microtime(true) + $time;

        while (true) {
            foreach ($urls as $url) {
                if (!isset($r[$url]) && $this->test($url)) {
                    $r[$url] = true;
                }
            }

            if (microtime(true) > $end_time) {
                break;
            }

            if (count($r) === count($urls)) {
                break;
            }
        }

        return count($r) === count($urls);
    }
}