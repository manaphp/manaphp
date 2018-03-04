<?php
namespace Tests;

use ManaPHP\Dom\CssToXpath;

class DomCssToXpathTest extends \PHPUnit_Framework_TestCase
{
    public function test_transform()
    {
        $cssToXpath = new CssToXpath();
        $css_xpaths = [

            //all selector
            '*' => '//*',

            //element types
            'div' => '//div',
            'a' => '//a',
            'span' => '//span',
            'h2' => '//h2',

            //style attributes
            '.error' => "//*[contains(concat(' ', normalize-space(@class), ' '), ' error ')]",
            'div.error' => "//div[contains(concat(' ', normalize-space(@class), ' '), ' error ')]",
            'label.required' => "//label[contains(concat(' ', normalize-space(@class), ' '), ' required ')]",

            //id attributes
            '#content' => "//*[@id='content']",
            'div#nav' => "//div[@id='nav']",

            //arbitrary attributes
            'div[bar="baz"]' => "//div[@bar='baz']",//exact match
            'div[bar="baz|xyz"]' => "//div[@bar='baz' or @bar='xyz']",//exact match

            'div[bar~="baz"]' => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ')]",//word match
            'div[bar~="baz|xyz"]' => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ') or contains(concat(' ', normalize-space(@bar), ' '), ' xyz ')]",//word match
            'div[bar~="baz&xyz"]' => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ') and contains(concat(' ', normalize-space(@bar), ' '), ' xyz ')]",//word match

            'div[bar*="baz"]' => "//div[contains(@bar, 'baz')]",//substring match
            'div[bar*="baz|xyz"]' => "//div[contains(@bar, 'baz') or contains(@bar, 'xyz')]",//substring match
            'div[bar*="baz&xyz"]' => "//div[contains(@bar, 'baz') and contains(@bar, 'xyz')]",//substring match

            'div[bar^="baz"]' => "//div[starts-with(@bar, 'baz')]",//starts with
            'div[bar^="baz|xyz"]' => "//div[starts-with(@bar, 'baz') or starts-with(@bar, 'xyz')]",//starts with
            'div[bar^="baz&xyz"]' => "//div[starts-with(@bar, 'baz') and starts-with(@bar, 'xyz')]",//starts with

            'div[bar$="baz"]' => "//div[ends-with(@bar, 'baz')]",//ends with
            'div[bar$="baz|xyz"]' => "//div[ends-with(@bar, 'baz') or ends-with(@bar, 'xyz')]",//ends with
            'div[bar$="baz&xyz"]' => "//div[ends-with(@bar, 'baz') and ends-with(@bar, 'xyz')]",//ends with

            //has attributes
            'div[bar]' => '//div[@bar]',//has bar attributes

            'div[="baz"]' => "//div[text()='baz']",//exact match
            'div[="baz|xyz"]' => "//div[text()='baz' or text()='xyz']",//exact match
            'div[="baz&xyz"]' => "//div[text()='baz' and text()='xyz']",//exact match

            'div[~="baz"]' => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ')]",//word match
            'div[~="baz|xyz"]' => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ') or contains(concat(' ', normalize-space(text()), ' '), ' xyz ')]",//word match
            'div[~="baz&xyz"]' => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ') and contains(concat(' ', normalize-space(text()), ' '), ' xyz ')]",//word match
            'div[*="baz"]' => "//div[contains(text(), 'baz')]",//substring match
            'div[*="baz|xyz"]' => "//div[contains(text(), 'baz') or contains(text(), 'xyz')]",//substring match
            'div[*="baz&xyz"]' => "//div[contains(text(), 'baz') and contains(text(), 'xyz')]",//substring match

            'div[^="baz"]' => "//div[starts-with(text(), 'baz')]",//starts with
            'div[^="baz|xyz"]' => "//div[starts-with(text(), 'baz') or starts-with(text(), 'xyz')]",//starts with
            'div[^="baz&xyz"]' => "//div[starts-with(text(), 'baz') and starts-with(text(), 'xyz')]",//starts with

            'div[$="baz"]' => "//div[ends-with(text(), 'baz')]",//ends with
            'div[$="baz|xyz"]' => "//div[ends-with(text(), 'baz') or ends-with(text(), 'xyz')]",//ends with
            'div[$="baz&xyz"]' => "//div[ends-with(text(), 'baz') and ends-with(text(), 'xyz')]",//ends with

            //direct descendents
            'div > span' => '//div/span',

            //descendents
            'div .foo span #one' => "//div//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]//span//*[@id='one']|//div[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]//span//*[@id='one']",

            'foo|bar' => '//foo|bar',

            //https://stackoverflow.com/questions/1604471/how-can-i-find-an-element-by-css-class-with-xpath
            'div>.foo' => "//div/[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]",
            'div > .foo' => "//div/[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]",
            'div>.foo.bar' => "//div/[contains(concat(' ', normalize-space(@class), ' '), ' foo ')][contains(concat(' ', normalize-space(@class), ' '), ' bar ')]",
            'div.bar span' => "//div[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]//span",
            'div > p.first' => "//div/p[contains(concat(' ', normalize-space(@class), ' '), ' first ')]",
            'a[rel="include"]' => "//a[@rel='include']",
            "a[rel='include']" => "//a[@rel='include']",

        ];

        foreach ($css_xpaths as $css => $xpath) {
            $this->assertEquals($xpath, $cssToXpath->transform($css),
                json_encode(['css' => $css, 'xpath' => $xpath], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        }
    }
}