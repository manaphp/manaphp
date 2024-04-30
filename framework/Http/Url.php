<?php
declare(strict_types=1);

namespace ManaPHP\Http;

use ManaPHP\Di\Attribute\Autowired;
use ManaPHP\Helper\SuppressWarnings;
use function is_string;

class Url implements UrlInterface
{
    #[Autowired] protected RequestInterface $request;
    #[Autowired] protected RouterInterface $router;

    public function get(string|array $args = [], bool|string $scheme = false): string
    {
        if (is_string($args)) {
            $url = $args;
            if ($url === '') {
                return $this->router->createUrl($url, $scheme);
            } elseif ($url[0] === '/') {
                $url = $this->router->getPrefix() . $url;
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
                $url = $this->router->getPrefix() . $url;
            } elseif (parse_url($url, PHP_URL_SCHEME)) {
                SuppressWarnings::noop();
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
                $scheme = $this->request->scheme();
            }
            return ($scheme === '//' ? $scheme : "$scheme://") . $this->request->header('host') . $url;
        } else {
            return $url;
        }
    }
}