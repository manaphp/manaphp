<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Url
 *
 * @package url
 *
 * @property \Application\Configure         $configure
 * @property \ManaPHP\Http\RequestInterface $request
 */
class Url extends Component implements UrlInterface
{
    /**
     * @var string
     */
    protected $_baseUri = '';

    /**
     * Url constructor.
     */
    public function __construct()
    {
        $this->_baseUri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    }

    /**
     * @param string $baseUri
     *
     * @return static
     */
    public function setBaseUri($baseUri)
    {
        $this->_baseUri = rtrim($baseUri, '/');

        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUri()
    {
        return $this->_baseUri;
    }

    /**
     * @param string $uri
     * @param array  $args
     *
     * @return string
     */
    public function get($uri = null, $args = [])
    {
        $strUri = $uri;
        if ($uri[0] === '/') {
            if ($uri === '/' || $uri[1] !== '/') {
                $strUri = $this->_baseUri . $uri;
            }
        }

        if (is_array($args)) {
            if (Text::contains($strUri, ':')) {
                foreach ($args as $k => $v) {
                    $count = 0;
                    $strUri = str_replace(':' . $k, $v, $strUri, $count);
                    if ($count !== 0) {
                        unset($args[$k]);
                    }
                }
            }

            if (count($args) !== 0) {
                $strUri = $strUri . (Text::contains($strUri, '?') ? '&' : '?') . http_build_query($args);
            }
        }

        return $strUri;
    }

    /**
     * @param string $uri
     * @param array  $args
     *
     * @return string
     */
    public function getFullUrl($uri = null, $args = [])
    {
        $url = $this->get($uri, $args);
        if (strpos($url, '://') === false) {
            $scheme = $this->request->getScheme();
            $host = $this->request->getServer('HTTP_HOST');
            $port = (int)$this->request->getServer('SERVER_PORT');
            if ($this->request->getScheme() === 'https') {
                return $scheme . '://' . $host . ($port === 443 ? '' : ':' . $port) . $url;
            } else {
                return $scheme . '://' . $host . ($port === 80 ? '' : ':' . $port) . $url;
            }
        } else {
            return $url;
        }
    }

    /**
     * @param string      $uri
     * @param bool|string $correspondingMin
     *
     * @return string
     */
    public function getCss($uri, $correspondingMin = true)
    {
        if ($this->configure->debug) {
            $strUri = $this->get($uri);
        } else {
            if ($correspondingMin === true) {
                $strUri = substr($this->get($uri), 0, -4) . '.min.css';
            } elseif ($correspondingMin === false) {
                $strUri = $this->get($uri);
            } else {
                $strUri = $this->get($correspondingMin);
            }
        }

        return $strUri;
    }

    /**
     * @param string      $uri
     * @param bool|string $correspondingMin
     *
     * @return string
     */
    public function getJs($uri, $correspondingMin = true)
    {
        if ($this->configure->debug) {
            $strUri = $this->get($uri);
        } else {
            if ($correspondingMin === true) {
                $strUri = substr($this->get($uri), 0, -3) . '.min.js';
            } elseif ($correspondingMin === false) {
                $strUri = $this->get($uri);
            } else {
                $strUri = $this->get($correspondingMin);
            }
        }

        return $strUri;
    }

    /**
     * @param string $uri
     *
     * @return string
     */
    public function getAsset($uri)
    {
        return $this->get($uri);
    }
}