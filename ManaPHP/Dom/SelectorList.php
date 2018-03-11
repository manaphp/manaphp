<?php
namespace ManaPHP\Dom;

class SelectorList implements \IteratorAggregate, \Countable, \ArrayAccess
{
    /**
     * @var \DOMNode[]
     */
    protected $_nodes;

    /**
     * @var \ManaPHP\Dom\Selector
     */
    protected $_root;

    /**
     * SelectorList constructor.
     *
     * @param \DOMNode[]                                      $nodes
     * @param \ManaPHP\Dom\Selector|\ManaPHP\Dom\SelectorList $root
     */
    public function __construct($nodes, $root)
    {
        $this->_nodes = $nodes;
        if ($root instanceof self) {
            $this->_root = $root->_root;
        } else {
            $this->_root = $root;
        }
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

        $query = $this->_root->_query;

        $nodes = [];
        foreach ($this->_nodes as $node) {
            /**
             * @var \DOMNode $node2
             */
            foreach ($query->xpath($path, $node) as $node2) {
                $nodes[$node2->getNodePath()] = $node2;
            }
        }

        return new SelectorList($nodes, $this);
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

        if ($css !== '' && $css[0] === '!') {
            $is_not = true;
            $css = substr($css, 1);
        } else {
            $is_not = false;
        }

        if ($pos = strpos($css, '::')) {
            $xpath = (new CssToXPath())->transform(substr($css, $pos + 2));
            $xpath = substr($css, 0, $pos + 2) . substr($xpath, 2);
        } else {
            $xpath = (new CssToXPath())->transform($css);
        }

        $query = $this->_root->_query;

        $nodes = [];
        foreach ($this->_nodes as $node) {
            /**
             * @var \DOMNode $node2
             */
            foreach ($query->xpath($is_not ? "not($xpath)" : $xpath, $node) as $node2) {
                $nodes[$node2->getNodePath()] = $node2;
            }
        }

        return new SelectorList($nodes, $this);
    }

    /**
     * @param string|static $selectors
     *
     * @return static
     */
    public function add($selectors)
    {
        if (is_string($selectors)) {
            $selectors = $this->_root->find($selectors);
        }

        if (!$selectors->_nodes) {
            return clone $this;
        }

        if (!$this->_nodes) {
            return clone $selectors;
        }

        /** @noinspection AdditionOperationOnArraysInspection */
        return new SelectorList($this->_nodes + $selectors->_nodes, $this);
    }

    /**@param string $css
     *
     * @return static
     */
    public function children($css = null)
    {
        return $this->css('child::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function closest($css = null)
    {
        return $this->css('ancestor-or-self::' . ($css === null ? '*' : $css));
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

        return $this;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function eq($index)
    {
        if ($index === 0) {
            return new SelectorList(count($this->_nodes) > 0 ? [current($this->_nodes)] : [], $this);
        }

        if ($index < 0) {
            $index = count($this->_nodes) + $index;
        }

        if ($index < 0 || $index >= count($this->_nodes)) {
            return new SelectorList([], $this);
        } else {
            $keys = array_keys($this->_nodes);
            return new SelectorList([$this->_nodes[$keys[$index]]], $this);
        }
    }

    /**
     * @param string          $field
     * @param callable|string $func
     *
     * @return static
     */
    public function filter($field = null, $func = null)
    {
        if ($field === '') {
            return clone $this;
        }

        if ($field !== null && $field[0] === '!') {
            $field = substr($field, 1);
            $not = true;
        } else {
            $not = false;
        }

        if (is_string($func)) {
            $is_preg = in_array($func[0], ['@', '#'], true) && substr_count($func, $func[0]) >= 2;
        }

        $nodes = [];
        foreach ($this->_nodes as $path => $node) {
            $selector = new Selector($node);
            if ($field === null) {
                $value = $selector;
            } elseif (strpos($field, '()') !== false) {
                if ($field === 'text()') {
                    $value = $selector->text();
                } elseif
                ($field === 'html()') {
                    $value = $selector->html();
                } elseif
                ($field === 'node()') {
                    $value = $node;
                } else {
                    throw new Exception('invalid field');
                }
            } else {
                $value = $selector->attr($field);
            }

            if ($func === null) {
                $r = $value !== '';
            } elseif (is_string($func)) {
                $r = $is_preg ? preg_match($func, $value) : strpos($value, $func) !== false;
            } else {
                $r = $func($value);
            }

            if (($not && !$r) || (!$not && $r)) {
                $nodes[$path] = $node;
            }
        }

        return new SelectorList($nodes, $this);
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function find($css = null)
    {
        return $this->css('descendant::' . ($css === null ? '*' : $css));
    }

    /**
     * @return \ManaPHP\Dom\Selector|null
     */
    public function first()
    {
        return count($this->_nodes) > 0 ? new Selector(current($this->_nodes)) : null;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function has($css)
    {
        return $this->css('child::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return bool
     */
    public function is($css)
    {
        $r = $this->css('self::' . ($css === null ? '*' : $css) . '[1]');
        return count($r->_nodes) > 0;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function next($css = null)
    {
        return $this->css('following-sibling::' . ($css === null ? '*' : $css) . '[1]');
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function nextAll($css = null)
    {
        return $this->css('following-sibling::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function not($css)
    {
        return $this->css('!self::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function parent($css = null)
    {
        if ($css === '') {
            return clone  $this;
        }

        return $this->css('parent::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function parents($css = null)
    {
        return $this->css('ancestor::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function prev($css = null)
    {
        return $this->css('preceding-sibling::' . ($css === null ? '*' : $css) . '[1]');
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function prevAll($css = null)
    {
        return $this->css('preceding-sibling::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function siblings($css = null)
    {
        $nodes = [];
        $query = $this->_root->_query;
        foreach ($this->_nodes as $node) {
            $cur_xpath = $node->getNodePath();
            foreach ($query->css('parent::' . ($css ?: '*'), $node) as $node2) {
                /**
                 * @var \DOMNode $node2
                 */
                /** @noinspection SlowArrayOperationsInLoopInspection */
                if ($node2->getNodePath() !== $cur_xpath) {
                    $nodes[$node2->getNodePath()] = $node2;
                }
            }
        }

        return new SelectorList($nodes, $this);
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
        return new SelectorList($nodes, $this);
    }

    /**
     * @return static
     */
    public function last()
    {
        return $this->eq(-1);
    }

    /**
     *
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
     * @param string $defaultValue
     *
     * @return string[][]
     */
    public function attr($attr = null, $defaultValue = null)
    {
        $data = [];

        foreach ($this->_nodes as $node) {
            $selector = new Selector($node);
            $data[] = $selector->attr($attr, $defaultValue);
        }

        return $data;
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
     * @param bool $as_string
     *
     * @return array
     */
    public function element($as_string = false)
    {
        $data = [];
        foreach ($this->_nodes as $node) {
            $data[] = (new Selector($node))->element($as_string);
        }

        return $data;
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
            $selectors[] = new Selector($node);
        }
        return new \ArrayIterator($selectors);
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
        return new Selector($this->_nodes[$offset]);
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
        return json_encode($this->text(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}