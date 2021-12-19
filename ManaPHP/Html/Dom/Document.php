<?php
declare(strict_types=1);

namespace ManaPHP\Html\Dom;

use DOMDocument;
use DOMElement;
use ManaPHP\Component;
use ManaPHP\Helper\LocalFS;
use ManaPHP\Html\Dom\Document\Exception as DocumentException;

/**
 * @property-read \ManaPHP\Http\ClientInterface $httpClient
 */
class Document extends Component
{
    protected string $url;
    protected string $base;
    protected string $str;
    protected DomDocument $dom;
    protected Query $query;
    protected array $errors = [];

    public function __construct(?string $str = null, ?string $url = null)
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

    public function loadFile(string $file, ?string $url = null): static
    {
        $str = LocalFS::fileGet($file);

        return $this->loadString($str, $url);
    }

    public function loadUrl(string $url): static
    {
        $str = $this->httpClient->get($url)->body;
        return $this->loadString($str, $url);
    }

    public function loadString(string $str, ?string $url = null): static
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
        $this->base = $this->getBase() ?: $this->url;

        return $this;
    }

    public function getString(bool $raw = true): string
    {
        return $raw ? $this->str : $this->dom->saveHTML($this->dom->documentElement);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function save(string $file): static
    {
        LocalFS::filePut($file, $this->getString());

        return $this;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }

    protected function getBase(): ?string
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

    public function setBase(string $url): static
    {
        $this->base = rtrim($url, '/') . '/';

        return $this;
    }

    public function saveHtml(?DOMElement $node = null): string
    {
        if ($node) {
            return $node->ownerDocument->saveHTML($node);
        } else {
            return $this->dom->saveHTML($this->dom);
        }
    }

    public function absolutizeUrl(string $url): string
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

    public function absolutizeAHref(?string $selector = null, ?DomDocument $context = null): static
    {
        /** @var \DOMElement $item */
        if ($selector) {
            foreach ($this->query->xpath($selector, $context) as $item) {
                if ($item->nodeName === 'a') {
                    $item->setAttribute('href', $this->absolutizeUrl($item->getAttribute('href')));
                } else {
                    $this->absolutizeAHref(null, $item);
                }
            }
        } else {
            foreach ($this->query->xpath("descendant:://a[not(starts-with(@href, 'http'))]", $context) as $item) {
                $item->setAttribute('href', $this->absolutizeUrl($item->getAttribute('href')));
            }
        }

        return $this;
    }

    public function absolutizeImgSrc(?string $selector = null, ?DOMElement $context = null, string $attr = 'src'
    ): static {
        /** @var \DOMElement $item */
        if ($selector) {
            foreach ($this->query->xpath($selector, $context) as $item) {
                if ($item->nodeName === 'a') {
                    $item->setAttribute($attr, $this->absolutizeUrl($item->getAttribute($attr)));
                } else {
                    $this->absolutizeImgSrc(null, $item);
                }
            }
        } else {
            foreach ($this->query->xpath("descendant:://a[not(starts-with(@$attr, 'http'))]", $context) as $item) {
                $item->setAttribute($attr, $this->absolutizeUrl($item->getAttribute($attr)));
            }
        }

        return $this;
    }

    public function selector(): Selector
    {
        return new Selector($this);
    }

    public function css(string|array $css): SelectorList
    {
        return $this->selector()->css($css);
    }
}