<?php

namespace ManaPHP\Html\Dom;

use DOMText;

class Selector
{
    /**
     * @var \ManaPHP\Html\Dom\Document
     */
    protected $_document;

    /**
     * @var \DOMElement
     */
    protected $_node;

    /**
     * @param string|\ManaPHP\Html\Dom\Document $document
     * @param \DOMNode                          $node
     */
    public function __construct($document, $node = null)
    {
        $this->_document = is_string($document) ? new Document($document) : $document;
        $this->_node = $node;
    }

    /**
     * @return static
     */
    public function root()
    {
        return new Selector($this->_document);
    }

    /**
     * @return \ManaPHP\Html\Dom\Document
     */
    public function document()
    {
        return $this->_document;
    }

    /**
     * @param string|array $query
     *
     * @return \ManaPHP\Html\Dom\SelectorList
     */
    public function xpath($query)
    {
        $nodes = [];
        /** @var \DOMNode $node */
        foreach ($this->_document->getQuery()->xpath($query, $this->_node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->_document, $nodes);
    }

    /**
     * @param string|array $css
     *
     * @return \ManaPHP\Html\Dom\SelectorList
     */
    public function css($css)
    {
        $nodes = [];
        /** @var \DOMNode $node */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->_document, $nodes);
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Html\Dom\SelectorList
     */
    public function find($css = null)
    {
        return $this->css('descendant::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Html\Dom\SelectorList
     */
    public function has($css)
    {
        return $this->css('child::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function remove($css)
    {
        /** @var \DOMNode $node */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $node->parentNode->removeChild($node);
        }

        return $this;
    }

    /**
     * @param string       $css
     * @param string|array $attr
     *
     * @return static
     */
    public function removeAttr($css, $attr = null)
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var \DOMElement $node */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            foreach ($node->attributes as $attribute) {
                if (!$attr || in_array($attribute->name, $attr, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        return $this;
    }

    /**
     * @param string       $css
     * @param string|array $attr
     *
     * @return static
     */
    public function retainAttr($css, $attr)
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var \DOMElement $node */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            foreach ($node->attributes as $attribute) {
                if (!in_array($attribute->name, $attr, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function strip($css)
    {
        /** @var \DOMNode $node */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $node->parentNode->replaceChild(new DOMText($node->textContent), $node);
        }

        return $this;
    }

    /**
     * @param string $attr
     *
     * @return string
     */
    public function attr($attr)
    {
        return $this->_node->getAttribute($attr);
    }

    /**
     * @param string $css
     * @param string $attr
     *
     * @return string|null
     */
    public function attr_first($css, $attr)
    {
        if ($nodes = $this->_document->getQuery()->css($css, $this->_node)) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            return $node->getAttribute($attr);
        } else {
            return null;
        }
    }

    /**
     * @param string $attr
     *
     * @return string
     */
    public function url($attr)
    {
        return $this->_document->absolutizeUrl($this->_node->getAttribute($attr));
    }

    /**
     * @param string $css
     * @param string $attr
     *
     * @return string|null
     */
    public function url_first($css, $attr)
    {
        if ($nodes = $this->_document->getQuery()->css($css, $this->_node)) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            return $this->_document->absolutizeUrl($node->getAttribute($attr));
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttr($name)
    {
        return $this->_node->hasAttribute($name);
    }

    /**
     * @return string
     */
    public function text()
    {
        return (string)$this->_node->textContent;
    }

    /**
     * @param string $css
     *
     * @return string|null
     */
    public function text_first($css)
    {
        $nodes = $this->_document->getQuery()->css($css, $this->_node);
        return $nodes ? $nodes->item(0)->textContent : null;
    }

    /**
     * @param array $rules
     *
     * @return array
     */
    public function extract($rules)
    {
        /** @var \DOMElement $node */
        $node = $this->_node;

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
                $nodes = $this->_document->getQuery()->css($rule, $node);
                $data[$name] = $nodes->length ? $nodes->item(0)->textContent : null;
            } else {
                if ($nodes = $this->_document->getQuery()->css(substr($rule, 0, $pos), $node)) {
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

    /**
     * @param string $css
     * @param array  $rules
     *
     * @return array
     */
    public function extract_first($css, $rules)
    {
        $nodes = $this->_document->getQuery()->css($css);

        if ($nodes) {
            return (new static($this->_document, $nodes->item(0)))->extract($rules);
        } else {
            return [];
        }
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->_node->nodeName;
    }

    /**
     * @return string
     */
    public function html()
    {
        return $this->_document->saveHtml($this->_node);
    }

    /**
     * @param string $css
     *
     * @return string|null
     */
    public function html_first($css)
    {
        if ($nodes = $this->_document->getQuery()->css($css, $this->_node)) {
            /** @var \DOMElement $node */
            $node = $nodes->item(0);
            return $node->ownerDocument->saveHTML($node);
        } else {
            return null;
        }
    }

    /**
     * @param string $regex
     *
     * @return array
     */
    public function links($regex = null)
    {
        /** @var \DOMElement $node */
        $data = [];
        foreach ($this->_document->getQuery()->xpath('descendant::a[@href]', $this->_node) as $node) {
            $href = $this->_document->absolutizeUrl($node->getAttribute('href'));

            if ($regex && !preg_match($regex, $href)) {
                continue;
            }

            $data[$node->getNodePath()] = ['href' => $href, 'text' => $node->textContent];
        }

        return $data;
    }

    /**
     * @param string $regex
     * @param string attr
     *
     * @return array
     */
    public function images($regex = null, $attr = 'src')
    {
        /** @var \DOMElement $node */
        $document = $this->_document;
        $data = [];
        foreach ($document->getQuery()->xpath("descendant::img[@$attr]", $this->_node) as $node) {
            $src = $document->absolutizeUrl($node->getAttribute($attr));

            if ($regex && !preg_match($regex, $src)) {
                continue;
            }

            $data[$node->getNodePath()] = $src;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function path()
    {
        return $this->_node->getNodePath();
    }

    /**
     * @return \DOMNode
     */
    public function node()
    {
        return $this->_node;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_node->getNodePath() ?: '';
    }
}