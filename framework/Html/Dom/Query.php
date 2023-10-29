<?php
declare(strict_types=1);

namespace ManaPHP\Html\Dom;

use DOMDocument;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use ManaPHP\Exception\MisuseException;

class Query
{
    protected DOMDocument $dom;
    protected DOMXPath $xpath;
    protected CssToXPath $cssToXPath;

    public function __construct(DOMDocument $domDocument)
    {
        $this->dom = $domDocument;

        $this->xpath = new DOMXPath($domDocument);

        $this->cssToXPath = new CssToXPath();
    }

    public function xpath(string|array $expression, ?DOMNode $context = null): DOMNodeList
    {
        if (\is_array($expression)) {
            $tr = [];
            foreach ($expression as $k => $v) {
                $tr['$' . $k] = \is_int($v) ? $v : "'$v'";
            }

            $expression = strtr($expression[0], $tr);
        }

        $r = @$this->xpath->query($expression, $context);
        if ($r === false) {
            throw new MisuseException(['`{xpath}` xpath is invalid expression', 'xpath' => $expression]);
        }

        return $r;
    }

    public function css(string|array $css, ?DOMNode $context = null): DOMNodeList
    {
        if ($css !== '' && $css[0] === '!') {
            $is_not = true;
            $css = substr($css, 1);
        } else {
            $is_not = false;
        }

        if ($pos = strpos($css, '::')) {
            $xpath = $this->cssToXPath->transform(substr($css, $pos + 2));
            $xpath = substr($css, 0, $pos + 2) . substr($xpath, 2);
        } else {
            $xpath = $this->cssToXPath->transform($css);
        }

        if ($context && $xpath[0] === '/') {
            $xpath = '.' . $xpath;
        }

        return $this->xpath($is_not ? "not($xpath)" : $xpath, $context);
    }
}