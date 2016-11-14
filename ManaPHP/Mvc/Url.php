<?php
namespace ManaPHP\Mvc;

use ManaPHP\Component;
use ManaPHP\Mvc\Url\Exception as UrlException;
use ManaPHP\Utility\Text;

/**
 * Class ManaPHP\Mvc\Url
 *
 * @package url
 *
 * @property \Application\Configure           $configure
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\Mvc\RouterInterface     $router
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 */
class Url extends Component implements UrlInterface
{
    /**
     * @var array
     */
    protected $_baseUrls = [];

    /**
     * Url constructor.
     *
     * @param array $options
     *
     * @throws \ManaPHP\Mvc\Url\Exception
     */
    public function __construct($options = [])
    {
        $selfPath = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
        if ($selfPath !== '/' && preg_match('#(.*)/public$#i', $selfPath, $match) === 1) {
            $selfPath = $match[1];
        } else {
            $selfPath = rtrim($selfPath, '/');
        }
        if (isset($options['baseUrls'])) {
            /** @noinspection ForeachSourceInspection */
            foreach ($options['baseUrls'] as $module => $path) {
                $this->_baseUrls[$module] = rtrim($path, '/');
            }

            if (!isset($this->_baseUrls[''])) {
                throw new UrlException('Default baseUrl is not set');
            }
        } else {
            foreach ($this->router->getModules() as $module => $path) {
                if ($path[0] === '/') {
                    $baseUri = $selfPath . ($path === '/' ? '' : $path);
                } else {
                    $baseUri = (strpos($path, '://') ? '' : $this->request->getScheme() . '://') . $path;
                }

                $this->_baseUrls[$module] = $baseUri;
            }

            $this->_baseUrls[''] = $this->_baseUrls[$this->dispatcher->getModuleName()];
        }

        if (!isset($this->_baseUrls['assets'])) {
            $this->_baseUrls['assets'] = $selfPath;
        }
    }

    /**
     * @param string|array $uri
     * @param array        $args
     * @param string       $module
     *
     * @return string
     * @throws \ManaPHP\Mvc\Url\Exception
     */
    public function get($uri = null, $args = [], $module = null)
    {
        if (is_array($uri)) {
            $tmp = $uri;

            $uri = $tmp[0];
            if (isset($tmp[1])) {
                if (is_array($tmp[1])) {
                    $args = $tmp[1];
                } else {
                    $module = $tmp[1];
                }
            }

            if (isset($tmp[2])) {
                $module = $tmp[2];
            }
        } else {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            if (is_string($args)) {
                $module = $args;
                $args = [];
            }
        }

        if ($uri[0] === '/') {
            if (!isset($this->_baseUrls[$module])) {
                if (isset($this->_baseUrls[ucfirst($module)])) {
                    throw new UrlException('module name is case-sensitive: `:module`', ['module' => $module]);
                } else {
                    throw new UrlException('`:module` is not exists', ['module' => $module]);
                }
            }
            $strUri = $this->_baseUrls[$module] . $uri;
        } else {
            $strUri = $uri;
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
     * @param string $module
     *
     * @return string
     * @throws \ManaPHP\Mvc\Url\Exception
     */
    public function getAbsolute($uri = null, $args = [], $module = null)
    {
        $url = $this->get($uri, $args, $module);
        if (strpos($url, '://') === false) {
            $scheme = $this->request->getScheme();
            $host = $this->request->getServer('HTTP_HOST');
            if ($this->request->getScheme() === 'https') {
                return $scheme . '://' . $host . $url;
            } else {
                return $scheme . '://' . $host . $url;
            }
        } else {
            return $url;
        }
    }

    /**
     * @param string $uri
     *
     * @return string
     * @throws \ManaPHP\Mvc\Url\Exception
     */
    public function getAsset($uri)
    {
        return $this->_baseUrls['assets'] . $uri;
    }
}