<?php
namespace ManaPHP\Dom;

class Selector
{
    /**
     * @var \ManaPHP\Dom\Query
     */
    protected $_query;

    /**
     * @var \DOMNode
     */
    protected $_node;

    /**
     * Selector constructor.
     *
     * @param string|\ManaPHP\Dom\Document|\DOMNode $docOrNode
     */
    public function __construct($docOrNode)
    {
        if ($docOrNode instanceof Document) {
            $this->_node = $docOrNode->getDom();
            $this->_query = new Query($this->_node);
        } elseif ($docOrNode instanceof \DOMNode) {
            $this->_node = $docOrNode;
        }
    }

    /**
     * @param string|array $query
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function xpath($query)
    {
        if (is_array($query)) {
            $tr = [];

            /** @noinspection ForeachSourceInspection */
            foreach ($query as $k => $v) {
                $tr['$' . $k] = is_int($v) ? $v : "'$v'";
            }

            $query = strtr($query[0], $tr);
        }

        $selectors = [];

        foreach ($this->_query->xpath($query, $this->_node) as $element) {
            $selector = new Selector($element);
            $selector->_query = $this->_query;
            $selectors[] = $selector;
        }
        return new SelectorList($selectors, [$query]);
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function css($css)
    {
        return $this->xpath((new CssToXPath())->transform($css));
    }

    /**
     * @return string
     */
    public function extract()
    {
        return (string)$this->_node;
    }

    /**
     * @return array
     */
    public function attr()
    {
        $data = [];

        foreach ($this->_node->attributes as $attribute) {
            $data[$attribute->name] = $attribute->value;
        }

        return $data;
    }

    /**
     * @return string
     */
    public function text()
    {
        return (string)$this->_node->textContent;
    }

    /**@param bool $as_string
     *
     * @return string|array
     */
    public function element($as_string = false)
    {
        return $as_string ? $this->html() : ['name' => $this->_node->nodeName, 'html' => $this->html(), 'text' => $this->text(), 'attr' => $this->attr(), 'xpath' => $this->_node->getNodePath()];
    }

    /**
     * @return string
     */
    public function html()
    {
        /**
         * @var \DOMNode $node
         */
        $node = $this->_node;

        return $node->ownerDocument->saveHTML($node);
    }

    /**
     * @return \DOMNode
     */
    public function node()
    {
        return $this->_node;
    }
}