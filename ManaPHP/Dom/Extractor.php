<?php
namespace ManaPHP\Dom;

class Extractor
{
    /**
     * @var \ManaPHP\Dom\Document
     */
    protected $_document;

    /**
     * Extractor constructor.
     */
    public function __construct($document)
    {
        $this->_document = $document;
    }

    /**
     * @param string $rule
     *
     * @return array
     */
    protected function _parseCleanRule($rule)
    {
        $rules = [[], []];

        $items = preg_split('#\s+#', $rule, PREG_SPLIT_NO_EMPTY);
        foreach ($items as $item) {
            if ($item[0] === '-') {
                $rules[0][] = substr($item, 1);
            } else {
                $rules[1][] = $item;
            }
        }

        return $rules;
    }

    /**
     * @param \DOMElement $node
     * @param string      $rule
     *
     * @return string
     */
    public function cleanText($node, $rule)
    {
        foreach ($this->_parseCleanRule($rule)[0] as $item) {
            $node->remove($item);
        }
    }

    /**
     * @param \DOMElement $node
     * @param string      $rule
     *
     * @return string
     */
    public function cleanHtml($node, $selectors)
    {

    }

    /**
     * @param array       $rule
     * @param \DOMElement $context
     *
     * @return array
     */
    public function extract($rule, $context = null)
    {
        /**
         * @var \DOMElement $node
         */
        $query = $this->_document->getQuery();

        $selector = $rule[0];
        $attr = isset($rule[1]) ? $rule[1] : 'html';


        $attr = strtr($attr, [' ' => '', "\t" => '']);
        $attr = trim($attr, ',');
        $attr = explode(',', $attr);

        $data = [];
        foreach ($query->css($selector, $context) as $node) {
            $val = [];
            foreach ($attr as $a) {
                if ($a === 'text') {
                    $val[$a] = $this->cleanText($node, isset($rule[2]) ? $rule[2] : null);
                } elseif ($a === 'html') {
                    $val[$a] = $this->cleanHtml($node, isset($rule[2]) ? $rule[2] : null);
                } elseif ($node->hasAttribute($a)) {
                    $val[$a] = $node->getAttribute($a);
                } else {
                    $val[$a] = null;
                }
            }
            $data[$node->getNodePath()] = count($attr) === 1 ? $val[$a] : $val;
        }

        return $data;
    }
}