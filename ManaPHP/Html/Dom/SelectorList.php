<?php

namespace ManaPHP\Html\Dom;

use ArrayAccess;
use ArrayIterator;
use Countable;
use DOMText;
use IteratorAggregate;

class SelectorList implements IteratorAggregate, Countable, ArrayAccess
{
    /**
     * @var \DOMElement[]
     */
    protected $_nodes;

    /**
     * @var \ManaPHP\Html\Dom\Document
     */
    protected $_document;

    /**
     * @param \ManaPHP\Html\Dom\Document|\ManaPHP\Html\Dom\SelectorList $document
     * @param \DOMNode[]                                                $nodes
     */
    public function __construct($document, $nodes)
    {
        $this->_document = $document instanceof self ? $document->_document : $document;
        $this->_nodes = $nodes;
    }

    /**
     * @param string $path
     *
     * @return static
     */
    public function xpath($path)
    {
        if ($path === '') {
            return clone $this;
        }
        $query = $this->_document->getQuery();

        $nodes = [];
        foreach ($this->_nodes as $node) {
            /** @var \DOMNode $node2 */
            foreach ($query->xpath($path, $node) as $node2) {
                $nodes[$node2->getNodePath()] = $node2;
            }
        }

        return new SelectorList($this, array_values($nodes));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function css($css)
    {
        if ($css === '') {
            return clone $this;
        }

        $query = $this->_document->getQuery();
        $nodes = [];
        foreach ($this->_nodes as $node) {
            /** @var \DOMNode $node2 */
            foreach ($query->css($css, $node) as $node2) {
                $nodes[$node2->getNodePath()] = $node2;
            }
        }

        return new SelectorList($this, array_values($nodes));
    }

    /**
     * @param string|\ManaPHP\Html\Dom\SelectorList $selectors
     *
     * @return static
     */
    public function add($selectors)
    {
        if (is_string($selectors)) {
            $selectors = (new Selector($this->_document))->find($selectors);
        }

        if (!$selectors->_nodes) {
            return clone $this;
        }

        if (!$this->_nodes) {
            return clone $selectors;
        }

        /** @noinspection AdditionOperationOnArraysInspection */
        return new SelectorList($this, $this->_nodes + $selectors->_nodes);
    }

    /**@param string $css
     *
     * @return static
     */
    public function children($css = null)
    {
        return $this->css('child::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function closest($css = null)
    {
        return $this->css('ancestor-or-self::' . ($css ?? '*'));
    }

    /**
     * @param callable $func
     *
     * @return array
     */
    public function each($func)
    {
        $data = [];
        foreach ($this->_nodes as $index => $selector) {
            $data[$index] = $func($selector, $index);
        }

        return $data;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function eq($index)
    {
        if ($index === 0) {
            return new SelectorList($this, $this->_nodes ? [current($this->_nodes)] : []);
        }

        if ($index < 0) {
            $index = count($this->_nodes) + $index;
        }

        if ($index < 0 || $index >= count($this->_nodes)) {
            return new SelectorList($this, []);
        } else {
            $keys = array_keys($this->_nodes);
            return new SelectorList($this, [$this->_nodes[$keys[$index]]]);
        }
    }

    /**
     * @param callable $func
     *
     * @return static
     */
    public function filter($func)
    {
        $index = 0;
        $nodes = [];
        foreach ($this->_nodes as $node) {
            $selector = new Selector($this->_document, $node);
            if ($func($selector, $index++) !== false) {
                $nodes[] = $node;
            }
        }

        return new SelectorList($this, $nodes);
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function find($css = null)
    {
        return $this->css('descendant::' . ($css ?? '*'));
    }

    /**
     * @return \ManaPHP\Html\Dom\Selector|null
     */
    public function first()
    {
        return count($this->_nodes) > 0 ? new Selector($this->_document, current($this->_nodes)) : null;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function has($css)
    {
        return $this->css('child::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return bool
     */
    public function is($css)
    {
        $r = $this->css('self::' . ($css ?? '*') . '[1]');
        return (bool)$r->_nodes;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function next($css = null)
    {
        return $this->css('following-sibling::' . ($css ?? '*') . '[1]');
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function nextAll($css = null)
    {
        return $this->css('following-sibling::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function not($css)
    {
        return $this->css('!self::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function parent($css = null)
    {
        if ($css === '') {
            return clone $this;
        }

        return $this->css('parent::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function parents($css = null)
    {
        return $this->css('ancestor::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function prev($css = null)
    {
        return $this->css('preceding-sibling::' . ($css ?? '*') . '[1]');
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function prevAll($css = null)
    {
        return $this->css('preceding-sibling::' . ($css ?? '*'));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function siblings($css = null)
    {
        $query = $this->_document->getQuery();

        $nodes = [];
        foreach ($this->_nodes as $node) {
            $cur_xpath = $node->getNodePath();
            foreach ($query->css('parent::' . ($css ?: '*'), $node) as $node2) {
                /** @var \DOMNode $node2 */
                if ($node2->getNodePath() !== $cur_xpath) {
                    $nodes[$node2->getNodePath()] = $node2;
                }
            }
        }

        return new SelectorList($this, array_values($nodes));
    }

    /**
     * @param int $offset
     * @param int $length
     *
     * @return static
     */
    public function slice($offset, $length = null)
    {
        $nodes = array_slice($this->_nodes, $offset, $length);
        return new SelectorList($this, $nodes);
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function remove($css)
    {
        /** @var \DOMNode $node */
        $query = $this->_document->getQuery();
        foreach ($this->_nodes as $node) {
            foreach ($query->css($css, $node) as $node2) {
                $node2->parentNode->removeChild($node2);
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
    public function removeAttr($css, $attr = null)
    {
        if ($attr) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /** @var \DOMElement $node */
        $query = $this->_document->getQuery();
        foreach ($this->_nodes as $node_0) {
            foreach ($query->css($css, $node_0) as $node) {
                foreach ($node->attributes as $attribute) {
                    if (!$attr || in_array($attribute->name, $attr, true)) {
                        $node->removeAttribute($attribute->name);
                    }
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
        $query = $this->_document->getQuery();
        foreach ($this->_nodes as $node_0) {
            foreach ($query->css($css, $node_0) as $node) {
                foreach ($node->attributes as $attribute) {
                    if (!in_array($attribute->name, $attr, true)) {
                        $node->removeAttribute($attribute->name);
                    }
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
        $query = $this->_document->getQuery();
        foreach ($this->_nodes as $node) {
            foreach ($query->css($css, $node) as $node2) {
                $node2->parentNode->replaceChild(new DOMText($node2->textContent), $node2);
            }
        }

        return $this;
    }

    /**
     * @return string[]|string
     */
    public function name()
    {
        $data = [];

        foreach ($this->_nodes as $node) {
            $data[] = $node->textContent;
        }

        return $data;
    }

    /**
     * @param string $attr
     *
     * @return string[]
     */
    public function attr($attr)
    {
        $data = [];

        foreach ($this->_nodes as $node) {
            $data[] = $node->getAttribute($attr);
        }

        return $data;
    }

    /**
     * @param string $attr
     *
     * @return string|null
     */
    public function attr_first($attr)
    {
        return $this->_nodes ? current($this->_nodes)->getAttribute($attr) : null;
    }

    /**
     * @return string[]
     */
    public function text()
    {
        $data = [];
        foreach ($this->_nodes as $node) {
            $data[] = $node->textContent;
        }

        return $data;
    }

    /**
     * @return string|null
     */
    public function text_first()
    {
        return $this->_nodes ? current($this->_nodes)->textContent : null;
    }

    /**
     * @param string $attr
     *
     * @return array
     */
    public function url($attr)
    {
        $data = [];

        foreach ($this->_nodes as $node) {
            $data[] = $this->_document->absolutizeUrl($node->getAttribute($attr));
        }

        return $data;
    }

    /**
     * @param string $attr
     *
     * @return string|null
     */
    public function url_first($attr)
    {
        return $this->_nodes ? $this->_document->absolutizeUrl(current($this->_nodes)->getAttribute($attr)) : null;
    }

    /**
     * @return string[]
     */
    public function html()
    {
        $data = [];
        foreach ($this->_nodes as $node) {
            $data[] = $node->ownerDocument->saveHTML($node);
        }

        return $data;
    }

    /**
     * @return string|null
     */
    public function html_first()
    {
        if ($this->_nodes) {
            $node = current($this->_nodes);
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
        /** @var \DOMElement $node2 */
        $query = $this->_document->getQuery();
        $document = $this->_document;

        $data = [];
        foreach ($this->_nodes as $node) {
            if ($node->nodeName === 'a') {
                $href = $document->absolutizeUrl($node->getAttribute('href'));

                if ($regex && !preg_match($regex, $href)) {
                    continue;
                }

                $data[$node->getNodePath()] = ['href' => $href, 'text' => $node->textContent];
            } else {
                foreach ($query->xpath('descendant::a[@href]', $node) as $node2) {
                    $href = $document->absolutizeUrl($node2->getAttribute('href'));

                    if ($regex && !preg_match($regex, $href)) {
                        continue;
                    }

                    $data[$node2->getNodePath()] = ['href' => $href, 'text' => $node2->textContent];
                }
            }
        }

        return array_values($data);
    }

    /**
     * @param string $regex
     * @param string $attr
     *
     * @return array
     */
    public function images($regex = null, $attr = 'src')
    {
        /** @var \DOMElement $node */
        /** @var \DOMElement $node2 */
        $query = $this->_document->getQuery();
        $document = $this->_document;

        $data = [];
        foreach ($this->_nodes as $node) {
            if ($node->nodeName === 'img') {
                $src = $document->absolutizeUrl($node->getAttribute($attr));

                if ($regex && !preg_match($regex, $src)) {
                    continue;
                }

                $data[$node->getNodePath()] = $src;
            } else {
                foreach ($query->xpath("descendant::img[@$attr]", $node) as $node2) {
                    $src = $document->absolutizeUrl($node2->getAttribute($attr));

                    if ($regex && !preg_match($regex, $src)) {
                        continue;
                    }

                    $data[$node2->getNodePath()] = $src;
                }
            }
        }

        return array_values($data);
    }

    /**
     * @param array $rules
     *
     * @return array[]
     */
    public function extract($rules)
    {
        $data = [];

        foreach ($this->_nodes as $node) {
            $selector = new Selector($this->_document, $node);
            $data[] = $selector->extract($rules);
        }

        return $data;
    }

    /**
     * @param array $rules
     *
     * @return array
     */
    public function extract_first($rules)
    {
        if ($this->_nodes) {
            $selector = new Selector($this->_document, current($this->_nodes));
            return $selector->extract($rules);
        } else {
            return [];
        }
    }

    /**
     * @return array
     */
    public function path()
    {
        $data = [];

        foreach ($this->_nodes as $node) {
            $data[] = $node->getNodePath();
        }

        return $data;
    }

    /**
     * @return \DOMNode[]
     */
    public function node()
    {
        return $this->_nodes;
    }

    /**
     * @return \ArrayIterator|\Traversable
     */
    public function getIterator()
    {
        $selectors = [];
        foreach ($this->_nodes as $node) {
            $selectors[] = new Selector($this->_document, $node);
        }
        return new ArrayIterator($selectors);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->_nodes);
    }

    public function offsetSet($offset, $value)
    {
    }

    public function offsetGet($offset)
    {
        return new Selector($this->_document, $this->_nodes[$offset]);
    }

    public function offsetExists($offset)
    {
        return isset($this->_nodes[$offset]);
    }

    public function offsetUnset($offset)
    {
    }

    public function __toString()
    {
        return (string)json_stringify($this->text());
    }
}