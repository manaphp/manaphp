<?php
namespace ManaPHP\Dom;

class Selector
{
    /**
     * @var \ManaPHP\Dom\Document
     */
    protected $_document;

    /**
     * @var \DOMElement
     */
    protected $_node;

    /**
     * Selector constructor.
     *
     * @param string|\ManaPHP\Dom\Document $document
     * @param \DOMNode                     $node
     */
    public function __construct($document, $node = null)
    {
        if (is_string($document)) {
            $document = (new Document())->load($document);
        }

        $this->_document = $document;
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
     * @return \ManaPHP\Dom\Document
     */
    public function document()
    {
        return $this->_document;
    }

    /**
     * @param string|array $query
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function xpath($query)
    {
        $nodes = [];
        /**
         * @var \DOMNode $node
         */
        foreach ($this->_document->getQuery()->xpath($query, $this->_node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->_document, $nodes);
    }

    /**
     * @param string|array $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function css($css)
    {
        $nodes = [];
        /**
         * @var \DOMNode $node
         */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->_document, $nodes);
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function find($css = null)
    {
        return $this->css('descendant::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string|array $attr
     * @param string       $defaultValue
     *
     * @return array|string
     */
    public function attr($attr = null, $defaultValue = null)
    {
        if ($this->_node instanceof \DOMElement) {
            $attributes = $this->_node->attributes;
        } else {
            $attributes = [];
        }

        if (is_string($attr)) {
            foreach ($attributes as $attribute) {
                if ($attribute->name === $attr) {
                    return $attribute->value;
                }
            }

            return $defaultValue;
        }

        $data = [];

        foreach ($attributes as $attribute) {
            $data[$attribute->name] = $attribute->value;
        }

        return $data;
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

    /**@param bool $as_string
     *
     * @return string|array
     */
    public function element($as_string = false)
    {
        if ($as_string) {
            return $this->html();
        }

        $data = [
            'name' => $this->_node->nodeName,
            'html' => $this->html(),
            'text' => $this->text(),
            'attr' => $this->attr(),
            'xpath' => $this->_node->getNodePath()
        ];

        return $data;
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
        /**
         * @var \DOMNode $node
         */
        $node = $this->_node;

        return $node->ownerDocument->saveHTML($node);
    }

    /**
     * @param string $regex
     *
     * @return array
     */
    public function links($regex = null)
    {
        /**
         * @var \DOMElement $node
         */
        $data = [];
        foreach ($this->_document->getQuery()->xpath('descendant::a[@href]', $this->_node) as $node) {
            $href = $this->_document->absolutizeUrl($node->getAttribute('href'));

            if ($regex && !preg_match($regex, $href)) {
                continue;
            }

            $data[$node->getNodePath()] = $href;
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
        /**
         * @var \DOMElement $node
         */
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
        return $this->_node->getNodePath();
    }
}