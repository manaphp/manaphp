<?php

namespace ManaPHP\Html\Dom;

use DOMXPath;
use ManaPHP\Exception\MisuseException;

class Query
{
    /**
     * @var \DOMDocument
     */
    protected $_dom;

    /**
     * @var \DOMXPath
     */
    protected $_xpath;

    /**
     * @var \ManaPHP\Html\Dom\CssToXPath
     */
    protected $_cssToXPath;

    /**
     * Query constructor.
     *
     * @param \DOMDocument $domDocument
     */
    public function __construct($domDocument)
    {
        $this->_dom = $domDocument;

        $this->_xpath = new DOMXPath($domDocument);

        $this->_cssToXPath = new CssToXPath();
    }

    /**
     * @param string|array $expression
     * @param \DOMNode     $context
     *
     * @return \DOMNodeList
     */
    public function xpath($expression, $context = null)
    {
        if (is_array($expression)) {
            $tr = [];
            foreach ($expression as $k => $v) {
                $tr['$' . $k] = is_int($v) ? $v : "'$v'";
            }

            $expression = strtr($expression[0], $tr);
        }

        $r = @$this->_xpath->query($expression, $context);
        if ($r === false) {
            throw new MisuseException(['`:xpath` xpath is invalid expression', 'xpath' => $expression]);
        }

        return $r;
    }

    /**
     * @param string|array $css
     * @param \DOMNode     $context
     *
     * @return \DOMNodeList
     */
    public function css($css, $context = null)
    {
        if ($css !== '' && $css[0] === '!') {
            $is_not = true;
            $css = substr($css, 1);
        } else {
            $is_not = false;
        }

        if ($pos = strpos($css, '::')) {
            $xpath = $this->_cssToXPath->transform(substr($css, $pos + 2));
            $xpath = substr($css, 0, $pos + 2) . substr($xpath, 2);
        } else {
            $xpath = $this->_cssToXPath->transform($css);
        }

        if ($context && $xpath[0] === '/') {
            $xpath = '.' . $xpath;
        }

        return $this->xpath($is_not ? "not($xpath)" : $xpath, $context);
    }
}