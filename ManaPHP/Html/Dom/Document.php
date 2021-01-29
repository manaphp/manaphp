<?php

namespace ManaPHP\Html\Dom;

use DOMDocument;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Html\Dom\Document\Exception as DocumentException;

class Document extends Component
{
    /**
     * @var string
     */
    protected $_source_url;

    /**
     * @var string
     */
    protected $_base_url;

    /**
     * @var string
     */
    protected $_str;

    /**
     * @var \DOMDocument
     */
    protected $_dom;

    /**
     * @var \ManaPHP\Html\Dom\Query
     */
    protected $_query;

    /**
     * @var array
     */
    protected $_errors = [];

    /**
     * @param string $str
     * @param string $url
     */
    public function __construct($str = null, $url = null)
    {
        if ($str !== null) {
            if (preg_match('#^https?://#', $str)) {
                $this->loadUrl($str);
            } elseif ($str[0] === '@' || $str[0] === '/' || $str[1] === ':') {
                $this->loadFile($str, $url);
            } else {
                $this->loadString($str, $url);
            }
        }
    }

    /**
     * @param string $file
     * @param string $url
     *
     * @return static
     */
    public function loadFile($file, $url = null)
    {
        $this->_source_url = $file;
        $str = LocalFS::fileGet($file);

        return $this->loadString($str, $url);
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function loadUrl($url)
    {
        $str = $this->httpClient->get($url)->body;
        return $this->loadString($str, $url);
    }

    /**
     * @param string $str
     * @param string $url
     *
     * @return static
     */
    public function loadString($str, $url = null)
    {
        $this->_str = $str;

        $this->_dom = new DOMDocument();
        $this->_dom->strictErrorChecking = false;

        libxml_clear_errors();
        $old_use_internal_errors = libxml_use_internal_errors(true);
        $old_disable_entity_loader = libxml_disable_entity_loader();

        /** @noinspection SubStrUsedAsStrPosInspection */
        if (substr($str, 0, 5) === '<?xml') {
            $r = $this->_dom->loadXML($str);
        } else {
            $r = $this->_dom->loadHTML($str, LIBXML_HTML_NODEFDTD);
        }

        $this->_errors = libxml_get_errors();
        libxml_clear_errors();

        libxml_disable_entity_loader($old_disable_entity_loader);
        libxml_use_internal_errors($old_use_internal_errors);

        if (!$r) {
            throw new DocumentException('xx');
        }

        $this->_query = $this->getNew('ManaPHP\Html\Dom\Query', [$this->_dom]);

        $this->_source_url = $url;
        $this->_base_url = $this->_getBaseUrl() ?: $this->_source_url;

        return $this;
    }

    /**
     * @param bool $raw
     *
     * @return string
     */
    public function getString($raw = true)
    {
        return $raw ? $this->_str : $this->_dom->saveHTML($this->_dom->documentElement);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->_errors;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function save($file)
    {
        LocalFS::filePut($file, $this->getString());

        return $this;
    }

    /**
     * @return \ManaPHP\Html\Dom\Query
     */
    public function getQuery()
    {
        return $this->_query;
    }

    /**
     * @return string
     */
    protected function _getBaseUrl()
    {
        foreach ($this->_dom->getElementsByTagName('base') as $node) {
            /** @var \DOMElement $node */
            if (!$node->hasAttribute('href')) {
                continue;
            }

            $href = $node->getAttribute('href');

            if (preg_match('#^https?://#', $href)) {
                return $href;
            } elseif ($href[0] === '/') {
                return substr($this->_source_url, 0, strpos($this->_source_url, '/', 10)) . $href;
            } else {
                return substr($this->_source_url, 0, strrpos($this->_source_url, '/', 10) + 1) . $href;
            }
        }

        return null;
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function setBaseUrl($url)
    {
        $this->_base_url = rtrim($url, '/') . '/';

        return $this;
    }

    /**
     * @param \DOMElement $node
     *
     * @return string
     */
    public function saveHtml($node = null)
    {
        if ($node) {
            return $node->ownerDocument->saveHTML($node);
        } else {
            return $this->_dom->saveHTML($this->_dom);
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function absolutizeUrl($url)
    {
        if (!$this->_base_url || preg_match('#^https?://#i', $url) || str_starts_with($url, 'javascript:')) {
            return $url;
        }

        if ($url === '') {
            return $this->_base_url;
        } elseif ($url[0] === '/') {
            return substr($this->_base_url, 0, strpos($this->_base_url, '/', 10)) . $url;
        } elseif ($url[0] === '#') {
            if (($pos = strrpos($this->_source_url, '#')) === false) {
                return $this->_source_url . $url;
            } else {
                return substr($this->_source_url, 0, $pos) . $url;
            }
        } else {
            return substr($this->_base_url, 0, strrpos($this->_base_url, '/', 10) + 1) . $url;
        }
    }

    /**
     * @param string      $selector
     * @param \DOMElement $context
     *
     * @return static
     */
    public function absolutizeAHref($selector = null, $context = null)
    {
        /** @var \DOMElement $item */
        if ($selector) {
            foreach ($this->_query->xpath($selector, $context) as $item) {
                if ($item->nodeName === 'a') {
                    $item->setAttribute('href', $this->absolutizeUrl($item->getAttribute('href')));
                } else {
                    $this->absolutizeAHref(null, $item);
                }
            }
        } else {
            foreach ($this->_query->xpath("descendant:://a[not(starts-with(@href, 'http'))]", $context) as $item) {
                $item->setAttribute('href', $this->absolutizeUrl($item->getAttribute('href')));
            }
        }

        return $this;
    }

    /**
     * @param string      $selector
     * @param \DOMElement $context
     * @param string      $attr
     *
     * @return static
     */
    public function absolutizeImgSrc($selector = null, $context = null, $attr = 'src')
    {
        /** @var \DOMElement $item */
        if ($selector) {
            foreach ($this->_query->xpath($selector, $context) as $item) {
                if ($item->nodeName === 'a') {
                    $item->setAttribute($attr, $this->absolutizeUrl($item->getAttribute($attr)));
                } else {
                    $this->absolutizeImgSrc(null, $item);
                }
            }
        } else {
            foreach ($this->_query->xpath("descendant:://a[not(starts-with(@$attr, 'http'))]", $context) as $item) {
                $item->setAttribute($attr, $this->absolutizeUrl($item->getAttribute($attr)));
            }
        }

        return $this;
    }

    /**
     * @return \ManaPHP\Html\Dom\Selector
     */
    public function selector()
    {
        return new Selector($this);
    }

    /**
     * @param string|array $css
     *
     * @return \ManaPHP\Html\Dom\SelectorList
     */
    public function css($css)
    {
        return $this->selector()->css($css);
    }
}