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
     * @var string
     */
    protected $_assets;

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
            foreach ($options['baseUrls'] as $module => $baseUrl) {
                $this->_baseUrls[$module] = rtrim($baseUrl, '/');
            }

        } else {
            $host = $this->request->getServer('HTTP_HOST');
            $scheme = $this->request->getScheme();

            foreach ($this->router->getModules() as $module => $path) {
                if ($path[0] === '/') {
                    $baseUrl = $scheme . '://' . $host . $selfPath . ($path === '/' ? '' : $path);
                } else {
                    $baseUrl = (strpos($path, '://') ? '' : $scheme . '://') . $path;
                }

                $this->_baseUrls[$module] = $baseUrl;
            }

            if (!isset($this->_baseUrls[''])) {
                $this->_baseUrls[''] = $this->_baseUrls[$this->dispatcher->getModuleName()];
            }
        }

        if (isset($options['assets'])) {
            $this->_assets = rtrim($options['assets'], '/');
        } else {
            $this->_assets = $selfPath;
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
    public function get($uri, $args = [], $module = null)
    {
        if (is_array($uri)) {
            $tmp = $uri;

            $uri = $tmp[0];

            if (isset($tmp[1])) {
                $args = $tmp[1];
            }

            if (isset($tmp[2])) {
                $module = $tmp[2];
            }
        }

        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        if (is_string($args)) {
            $module = $args;
            $args = [];
        }

        if (!isset($this->_baseUrls[$module])) {
            if (isset($this->_baseUrls[ucfirst($module)])) {
                throw new UrlException('module name is case-sensitive: `:module`', ['module' => $module]);
            } else {
                throw new UrlException('`:module` is not exists', ['module' => $module]);
            }
        }

        if ($uri === '' || $uri[0] !== '/') {
            $baseUrl = $this->_baseUrls[$module];
            $strUrl = (strpos($baseUrl, '://') ? parse_url($baseUrl, PHP_URL_PATH) : $baseUrl).'/' . $uri;
        } else {
            $strUrl = $this->_baseUrls[$module] . $uri;
        }

        if (Text::contains($strUrl, ':')) {
            foreach ($args as $k => $v) {
                $count = 0;
                $strUrl = str_replace(':' . $k, $v, $strUrl, $count);
                if ($count !== 0) {
                    unset($args[$k]);
                }
            }
        }
        if (count($args) !== 0) {
            $strUrl .= (Text::contains($strUrl, '?') ? '&' : '?') . http_build_query($args);
        }

        return $strUrl;
    }

    /**
     * @param string $uri
     *
     * @return string
     * @throws \ManaPHP\Mvc\Url\Exception
     */
    public function getAsset($uri)
    {
        return $this->_assets . $uri;
    }
}