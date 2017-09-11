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
        if ($selfPath !== '/' && preg_match('#(.*)/public$#', $selfPath, $match) === 1) {
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

        $this->_assets = isset($options['assets']) ? rtrim($options['assets'], '/') : $selfPath;
    }

    /**
     * @param string|array $args
     * @param string       $module
     *
     * @return string
     * @throws \ManaPHP\Mvc\Url\Exception
     */
    public function get($args = [], $module = null)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        if (is_string($args)) {
            $uri = $args;
            $args = [];
        } else {
            $uri = $args[0];
            unset($args[0]);

            if (isset($args['#'])) {
                $anchor = $args['#'];
                unset($args['#']);
            }
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
            $strUrl = (strpos($baseUrl, '://') ? parse_url($baseUrl, PHP_URL_PATH) : $baseUrl) . '/' . $uri;
        } else {
            $strUrl = $this->_baseUrls[$module] . $uri;
        }

        if (Text::contains($strUrl, ':')) {
            /** @noinspection ForeachSourceInspection */
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

        if (isset($anchor)) {
            $strUrl .= '#' . $anchor;
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