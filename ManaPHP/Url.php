<?php
namespace ManaPHP;

/**
 * Class ManaPHP\Url
 *
 * @package url
 * @property-read \ManaPHP\Http\RequestInterface $request
 *
 */
class Url extends Component implements UrlInterface
{
    /**
     * @param string|array $args
     * @param string|bool  $scheme
     *
     * @return string
     */
    public function get($args = [], $scheme = false)
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

        $prefix = $this->alias->resolve('@web');
        if ($uri === '') {
            $strUrl = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        } elseif ($uri[0] !== '/') {
            $strUrl = (strpos($prefix, '://') ? parse_url($prefix, PHP_URL_PATH) : $prefix) . '/' . $uri;
        } else {
            $strUrl = ($prefix === '/' ? '' : rtrim($prefix, '/')) . $uri;
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

        if ($scheme === true) {
            $scheme = $this->request->getScheme();
        }

        if ($scheme) {
            return $scheme . ($scheme === '//' ? '' : '://') . $_SERVER['HTTP_HOST'] . $strUrl;
        }

        return $strUrl;
    }
}