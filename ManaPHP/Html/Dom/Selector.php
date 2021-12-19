<?php
declare(strict_types=1);

namespace ManaPHP\Html\Dom;

use DOMText;
use DOMElement;
use DOMNode;

class Selector
{
    protected Document $document;
    protected DOMElement $node;

    public function __construct(string|Document $document, ?DOMElement $node = null)
    {
        $this->document = is_string($document) ? new Document($document) : $document;
        $this->node = $node;
    }

    public function root(): static
    {
        return new Selector($this->document);
    }

    public function document(): Document
    {
        return $this->document;
    }

    public function xpath(string|array $query): SelectorList
    {
        $nodes = [];
        /** @var \DOMNode $node */
        foreach ($this->document->getQuery()->xpath($query, $this->node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->document, $nodes);
    }

    public function css(string|array $css): SelectorList
    {
        $nodes = [];
        /** @var \DOMNode $node */
        foreach ($this->document->getQuery()->css($css, $this->node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->document, $nodes);
    }

    public function find(?string $css = null): SelectorList
    {
        return $this->css('descendant::' . ($css ?? '*'));
    }

    public function has(string $css): SelectorList
    {
        return $this->css('child::' . ($css ?? '*'));
    }

    public function remove(string $css): static
    {
        /** @var \DOMNode $node */
        foreach ($this->document->getQuery()->css($css, $this->node) as $node) {
            $node->parentNode->removeChild($node);
        }

        return $this;
    }

    public function removeAttr(string $css, mixed $attr = null): static
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var \DOMElement $node */
        foreach ($this->document->getQuery()->css($css, $this->node) as $node) {
            foreach ($node->attributes as $attribute) {
                if (!$attr || in_array($attribute->name, $attr, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        return $this;
    }

    public function retainAttr(string $css, string|array $attr): static
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var \DOMElement $node */
        foreach ($this->document->getQuery()->css($css, $this->node) as $node) {
            foreach ($node->attributes as $attribute) {
                if (!in_array($attribute->name, $attr, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        return $this;
    }

    public function strip(string $css): static
    {
        /** @var \DOMNode $node */
        foreach ($this->document->getQuery()->css($css, $this->node) as $node) {
            $node->parentNode->replaceChild(new DOMText($node->textContent), $node);
        }

        return $this;
    }

    public function attr(string $attr): string
    {
        return $this->node->getAttribute($attr);
    }

    public function attr_first(string $css, string $attr): ?string
    {
        if ($nodes = $this->document->getQuery()->css($css, $this->node)) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            return $node->getAttribute($attr);
        } else {
            return null;
        }
    }

    public function url(string $attr): string
    {
        return $this->document->absolutizeUrl($this->node->getAttribute($attr));
    }

    public function url_first(string $css, string $attr): ?string
    {
        if ($nodes = $this->document->getQuery()->css($css, $this->node)) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            return $this->document->absolutizeUrl($node->getAttribute($attr));
        } else {
            return null;
        }
    }

    public function hasAttr(string $name): bool
    {
        return $this->node->hasAttribute($name);
    }

    public function text(): string
    {
        return $this->node->textContent;
    }

    public function text_first(string $css): ?string
    {
        $nodes = $this->document->getQuery()->css($css, $this->node);
        return $nodes ? $nodes->item(0)->textContent : null;
    }

    public function extract(array $rules): array
    {
        /** @var \DOMElement $node */
        $node = $this->node;

        $data = [];
        foreach ($rules as $name => $rule) {
            if ($rule[0] === '@') {
                $data[$name] = $node->getAttribute(substr($rule, 1));
            } elseif ($rule === 'text()') {
                $data[$name] = $node->textContent;
            } elseif ($rule === 'html()') {
                $data[$name] = $node->ownerDocument->saveHTML($node);
            } elseif ($rule === 'path()') {
                $data[$name] = $node->getNodePath();
            } elseif (($pos = strpos($rule, '@')) === false) {
                $nodes = $this->document->getQuery()->css($rule, $node);
                $data[$name] = $nodes->length ? $nodes->item(0)->textContent : null;
            } else {
                if ($nodes = $this->document->getQuery()->css(substr($rule, 0, $pos), $node)) {
                    /** @var \DOMElement $node_temp */
                    $node_temp = $nodes->item(0);
                    $data[$name] = $node_temp->getAttribute(substr($rule, $pos + 1));
                } else {
                    $data[$name] = null;
                }
            }
        }

        return $data;
    }

    public function extract_first(string $css, array $rules): array
    {
        $nodes = $this->document->getQuery()->css($css);

        if ($nodes) {
            return (new static($this->document, $nodes->item(0)))->extract($rules);
        } else {
            return [];
        }
    }

    public function name(): string
    {
        return $this->node->nodeName;
    }

    public function html(): string
    {
        return $this->document->saveHtml($this->node);
    }

    public function html_first(string $css): ?string
    {
        if ($nodes = $this->document->getQuery()->css($css, $this->node)) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            return $node->ownerDocument->saveHTML($node);
        } else {
            return null;
        }
    }

    public function links(?string $regex = null): array
    {
        /** @var \DOMElement $node */
        $data = [];
        foreach ($this->document->getQuery()->xpath('descendant::a[@href]', $this->node) as $node) {
            $href = $this->document->absolutizeUrl($node->getAttribute('href'));

            if ($regex && !preg_match($regex, $href)) {
                continue;
            }

            $data[$node->getNodePath()] = ['href' => $href, 'text' => $node->textContent];
        }

        return $data;
    }

    public function images(?string $regex = null, string $attr = 'src'): array
    {
        /** @var \DOMElement $node */
        $document = $this->document;
        $data = [];
        foreach ($document->getQuery()->xpath("descendant::img[@$attr]", $this->node) as $node) {
            $src = $document->absolutizeUrl($node->getAttribute($attr));

            if ($regex && !preg_match($regex, $src)) {
                continue;
            }

            $data[$node->getNodePath()] = $src;
        }

        return $data;
    }

    public function path(): string
    {
        return $this->node->getNodePath();
    }

    public function node(): DOMNode
    {
        return $this->node;
    }

    public function __toString(): string
    {
        return $this->node->getNodePath() ?: '';
    }
}