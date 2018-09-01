<?php
namespace ManaPHP\View;

use ManaPHP\Component;

/**
 * Class ManaPHP\View\Url
 *
 * @package url
 *
 * @property \ManaPHP\Configuration\Configure $configure
 * @property \ManaPHP\Http\RequestInterface   $request
 * @property \ManaPHP\RouterInterface         $router
 * @property \ManaPHP\Mvc\DispatcherInterface $dispatcher
 */
class Url extends Component implements UrlInterface
{
    /**
     * @var string
     */
    protected $_prefix;

    /**
     * Url constructor.
     *
     * @param array $options
     */
    public function __construct($options = [])
    {
        $this->_prefix = $this->router->getPrefix();
        if ($this->_prefix[0] === '/') {
            $this->_prefix = rtrim($this->alias->resolve('@web') . $this->_prefix, '/');
        }
    }

    /**
     * @param string|array $args
     *
     * @return string
     */
    public function get($args = [])
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $anchor = null;
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

        if ($uri === '' || $uri[0] !== '/') {
            $strUrl = (strpos($this->_prefix, '://') ? parse_url($this->_prefix, PHP_URL_PATH) : $this->_prefix) . '/' . $uri;
        } else {
            $strUrl = ($this->_prefix === '/' ? '' : rtrim($this->_prefix, '/')) . $uri;
        }

        if (strpos($strUrl, ':') !== false) {
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
            $strUrl .= (strpos($strUrl, '?') !== false ? '&' : '?') . http_build_query($args);
        }

        if ($anchor !== null) {
            $strUrl .= '#' . $anchor;
        }

        return $strUrl;
    }
}