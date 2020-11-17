<?php

namespace ManaPHP\Http;

use ManaPHP\Component;

/**
 * @property-read \ManaPHP\Http\RequestInterface $request
 * @property-read \ManaPHP\Http\RouterInterface  $router
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
        if (is_string($args)) {
            $url = $args;
            if ($url === '') {
                return $this->router->createUrl($url, $scheme);
            } elseif ($url[0] === '/') {
                $url = $this->alias->get('@web') . $url;
                if (!$scheme) {
                    return $url;
                }
            } elseif (parse_url($url, PHP_URL_SCHEME)) {
                return $url;
            } else {
                return $this->router->createUrl($url, $scheme);
            }
            $anchor = null;
            $args = '';
        } else {
            $url = $args[0];
            if ($url === '') {
                return $this->router->createUrl($args, $scheme);
            } elseif ($url[0] === '/') {
                $url = $this->alias->get('@web') . $url;
            } elseif (parse_url($url, PHP_URL_SCHEME)) {
                null;
            } else {
                return $this->router->createUrl($args, $scheme);
            }

            $anchor = $args['#'] ?? null;
            unset($args[0], $args['#']);
        }

        if ($args) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($args);
        }

        if ($anchor !== null) {
            $url .= '#' . $anchor;
        }

        if ($scheme) {
            if ($scheme === true) {
                $scheme = $this->request->getScheme();
            }
            return ($scheme === '//' ? $scheme : "$scheme://") . $this->request->getServer('HTTP_HOST') . $url;
        } else {
            return $url;
        }
    }
}