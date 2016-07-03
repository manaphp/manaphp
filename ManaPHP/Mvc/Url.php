<?php
namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Utility\Text;

    class Url extends Component implements UrlInterface
    {
        protected $_baseUri = '';

        public function __construct()
        {
            parent::__construct();

            $this->_baseUri = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
        }

        public function setBaseUri($baseUri)
        {
            $this->_baseUri = rtrim($baseUri, '/');

            return $this;
        }

        public function getBaseUri()
        {
            return $this->_baseUri;
        }

        public function get($uri = null, $args = null)
        {
            $strUri = $uri;
            if ($uri[0] === '/') {
                if ($uri === '/' || $uri[1] !== '/') {
                    $strUri = $this->_baseUri . $uri;
                }
            }

            if (is_array($args)) {
                if (Text::contains($strUri, '{')) {
                    foreach ($args as $k => $v) {
                        $strUri = str_replace('{' . $k . '}', $v, $strUri, $count);
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
    }
}