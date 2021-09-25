<?php

namespace ManaPHP\Html\Dom;

use DOMDocument;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Html\Dom\Document\Exception as DocumentException;

/**
 * @property-read \ManaPHP\Http\ClientInterface $httpClient
 */
class Document extends Component
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var string
     */
    protected $base;

    /**
     * @var string
     */
    protected $str;

    /**
     * @var \DOMDocument
     */
    protected $dom;

    /**
     * @var \ManaPHP\Html\Dom\Query
     */
    protected $query;

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * @param string $str
     * @param string $url
     */
    public function __construct($str = null, $url = null)
    {
        if ($str !== null) {
            if (preg_match('#^https?://#', $str)) {
                $this->self->loadUrl($str);
            } elseif ($str[0] === '@' || $str[0] === '/' || $str[1] === ':') {
                $this->self->loadFile($str, $url);
            } else {
                $this->self->loadString($str, $url);
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
        $str = LocalFS::fileGet($file);

        return $this->self->loadString($str, $url);
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function loadUrl($url)
    {
        $str = $this->httpClient->get($url)->body;
        return $this->self->loadString($str, $url);
    }

    /**
     * @param string $str
     * @param string $url
     *
     * @return static
     */
    public function loadString($str, $url = null)
    {
        $this->str = $str;

        $this->dom = new DOMDocument();
        $this->dom->strictErrorChecking = false;

        libxml_clear_errors();
        $old_use_internal_errors = libxml_use_internal_errors(true);

        $old_disable_entity_loader = true;
        if (PHP_VERSION_ID < 80000) {
            /** @noinspection PhpDeprecationInspection */
            $old_disable_entity_loader = libxml_disable_entity_loader();
        }

        /** @noinspection SubStrUsedAsStrPosInspection */
        if (substr($str, 0, 5) === '<?xml') {
            $r = $this->dom->loadXML($str);
        } else {
            $r = $this->dom->loadHTML($str, LIBXML_HTML_NODEFDTD);
        }

        $this->errors = libxml_get_errors();
        libxml_clear_errors();

        if (PHP_VERSION_ID < 80000) {
            /** @noinspection PhpDeprecationInspection */
            libxml_disable_entity_loader($old_disable_entity_loader);
        }

        libxml_use_internal_errors($old_use_internal_errors);

        if (!$r) {
            throw new DocumentException('xx');
        }

        $this->query = $this->container->make('ManaPHP\Html\Dom\Query', [$this->dom]);

        $this->url = $url;
        $this->base = $this->self->getBase() ?: $this->url;

        return $this;
    }

    /**
     * @param bool $raw
     *
     * @return string
     */
    public function getString($raw = true)
    {
        return $raw ? $this->str : $this->dom->saveHTML($this->dom->documentElement);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $file
     *
     * @return static
     */
    public function save($file)
    {
        LocalFS::filePut($file, $this->self->getString());

        return $this;
    }

    /**
     * @return \ManaPHP\Html\Dom\Query
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @return string
     */
    protected function getBase()
    {
        foreach ($this->dom->getElementsByTagName('base') as $node) {
            /** @var \DOMElement $node */
            if (!$node->hasAttribute('href')) {
                continue;
            }

            $href = $node->getAttribute('href');

            if (preg_match('#^https?://#', $href)) {
                return $href;
            } elseif ($href[0] === '/') {
                return substr($this->url, 0, strpos($this->url, '/', 10)) . $href;
            } else {
                return substr($this->url, 0, strrpos($this->url, '/', 10) + 1) . $href;
            }
        }

        return null;
    }

    /**
     * @param string $url
     *
     * @return static
     */
    public function setBase($url)
    {
        $this->base = rtrim($url, '/') . '/';

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
            return $this->dom->saveHTML($this->dom);
        }
    }

    /**
     * @param string $url
     *
     * @return string
     */
    public function absolutizeUrl($url)
    {
        if (!$this->base || preg_match('#^https?://#i', $url) || str_starts_with($url, 'javascript:')) {
            return $url;
        }

        if ($url === '') {
            return $this->base;
        } elseif ($url[0] === '/') {
            return substr($this->base, 0, strpos($this->base, '/', 10)) . $url;
        } elseif ($url[0] === '#') {
            if (($pos = strrpos($this->url, '#')) === false) {
                return $this->url . $url;
            } else {
                return substr($this->url, 0, $pos) . $url;
            }
        } else {
            return substr($this->base, 0, strrpos($this->base, '/', 10) + 1) . $url;
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
            foreach ($this->query->xpath($selector, $context) as $item) {
                if ($item->nodeName === 'a') {
                    $item->setAttribute('href', $this->self->absolutizeUrl($item->getAttribute('href')));
                } else {
                    $this->self->absolutizeAHref(null, $item);
                }
            }
        } else {
            foreach ($this->query->xpath("descendant:://a[not(starts-with(@href, 'http'))]", $context) as $item) {
                $item->setAttribute('href', $this->self->absolutizeUrl($item->getAttribute('href')));
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
            foreach ($this->query->xpath($selector, $context) as $item) {
                if ($item->nodeName === 'a') {
                    $item->setAttribute($attr, $this->self->absolutizeUrl($item->getAttribute($attr)));
                } else {
                    $this->self->absolutizeImgSrc(null, $item);
                }
            }
        } else {
            foreach ($this->query->xpath("descendant:://a[not(starts-with(@$attr, 'http'))]", $context) as $item) {
                $item->setAttribute($attr, $this->self->absolutizeUrl($item->getAttribute($attr)));
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
        return $this->self->selector()->css($css);
    }
}