<?php
namespace ManaPHP\Mvc {

    use ManaPHP\Component;
    use ManaPHP\Mvc\Url\Exception;
    use ManaPHP\Utility\Text;

    class Url extends Component implements UrlInterface
    {
        protected $_prefix = '';

        public function __construct()
        {
            $this->_prefix = rtrim(str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])), '/');
        }

        /**
         * Sets a prefix to all the urls generated
         *
         * @param string $prefix
         *
         * @return static
         * @throws \ManaPHP\Mvc\Url\Exception
         */
        public function setPrefix($prefix)
        {
            if ($prefix !== '' && $prefix[0] !== '/') {
                throw new Exception('Url Prefix must star with \'/\'');
            }

            $this->_prefix = rtrim($prefix, '/');
            return $this;
        }

        /**
         * Returns the prefix for all the generated urls.
         */
        public function getPrefix()
        {
            return $this->_prefix;
        }

        /**
         * @param string $uri
         * @param array  $args
         *
         * @return mixed
         */
        public function get($uri = null, $args = null)
        {
            $strUri = $uri;
            if ($uri[0] === '/') {
                if ($uri === '/' || $uri[1] !== '/') {
                    $strUri = $this->_prefix . $uri;
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
    }
}