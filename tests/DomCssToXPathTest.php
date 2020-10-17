<?php

namespace Tests;

use ManaPHP\Dom\CssToXPath;

class DomCssToXPathTest extends \PHPUnit_Framework_TestCase
{
    public function test_transform()
    {
        $cssToXpath = new CssToXPath();
        $css_xpaths = [

            //all selector
            '*'                    => '//*',

            //element types
            'div'                  => '//div',
            'a'                    => '//a',
            'span'                 => '//span',
            'h2'                   => '//h2',

            //style attributes
            '.error'               => "//*[contains(concat(' ', normalize-space(@class), ' '), ' error ')]",
            'div.error'            => "//div[contains(concat(' ', normalize-space(@class), ' '), ' error ')]",
            'label.required'       => "//label[contains(concat(' ', normalize-space(@class), ' '), ' required ')]",
            'label.error.required' => "//label[contains(concat(' ', normalize-space(@class), ' '), ' error ')][contains(concat(' ', normalize-space(@class), ' '), ' required ')]",
            //id attributes
            '#content'             => "//*[@id='content']",
            'div#nav'              => "//div[@id='nav']",

            //has attributes
            'div[bar]'             => '//div[@bar]',//has bar attributes
            'div[bar|xyz]'         => '//div[@bar or @xyz]',//has bar attributes
            'div[bar&xyz]'         => '//div[@bar and @xyz]',//has bar attributes

            //arbitrary attributes
            'div[bar=baz]'         => "//div[@bar='baz']",//exact match
            'div[bar="baz"]'       => "//div[@bar='baz']",//exact match
            "div[bar='baz']"       => "//div[@bar='baz']",//exact match
            'div[bar="baz|xyz"]'   => "//div[@bar='baz' or @bar='xyz']",//exact match
            'div[bar="baz&xyz"]'   => "//div[@bar='baz' and @bar='xyz']",//exact match

            'div[bar~=baz]'       => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ')]",//word match
            'div[bar~="baz"]'     => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ')]",//word match
            'div[bar~="baz|xyz"]' => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ') or contains(concat(' ', normalize-space(@bar), ' '), ' xyz ')]",
            //word match
            'div[bar~="baz&xyz"]' => "//div[contains(concat(' ', normalize-space(@bar), ' '), ' baz ') and contains(concat(' ', normalize-space(@bar), ' '), ' xyz ')]",
            //word match

            'div[bar|=baz]'       => "//div[@bar='baz' or starts-with(@bar,'baz-')]",
            'div[bar|="baz"]'     => "//div[@bar='baz' or starts-with(@bar,'baz-')]",
            'div[bar|="baz|xyz"]' => "//div[(@bar='baz' or starts-with(@bar,'baz-')) or (@bar='xyz' or starts-with(@bar,'xyz-'))]",
            'div[bar|="baz&xyz"]' => "//div[(@bar='baz' or starts-with(@bar,'baz-')) and (@bar='xyz' or starts-with(@bar,'xyz-'))]",

            'div[bar!=baz]'       => "//div[not(@bar) or @bar!='baz']",
            'div[bar!="baz"]'     => "//div[not(@bar) or @bar!='baz']",
            'div[bar!="baz|xyz"]' => "//div[(not(@bar) or @bar!='baz') or (not(@bar) or @bar!='xyz')]",
            'div[bar!="baz&xyz"]' => "//div[(not(@bar) or @bar!='baz') and (not(@bar) or @bar!='xyz')]",

            'div[bar^=baz]'       => "//div[starts-with(@bar, 'baz')]",//starts with
            'div[bar^="baz"]'     => "//div[starts-with(@bar, 'baz')]",//starts with
            'div[bar^="baz|xyz"]' => "//div[starts-with(@bar, 'baz') or starts-with(@bar, 'xyz')]",//starts with
            'div[bar^="baz&xyz"]' => "//div[starts-with(@bar, 'baz') and starts-with(@bar, 'xyz')]",//starts with

            'div[bar$=baz]'       => "//div[ends-with(@bar, 'baz')]",//ends with
            'div[bar$="baz"]'     => "//div[ends-with(@bar, 'baz')]",//ends with
            'div[bar$="baz|xyz"]' => "//div[ends-with(@bar, 'baz') or ends-with(@bar, 'xyz')]",//ends with
            'div[bar$="baz&xyz"]' => "//div[ends-with(@bar, 'baz') and ends-with(@bar, 'xyz')]",//ends with

            'div[bar*=baz]'       => "//div[contains(@bar, 'baz')]",//substring match
            'div[bar*="baz"]'     => "//div[contains(@bar, 'baz')]",//substring match
            'div[bar*="baz|xyz"]' => "//div[contains(@bar, 'baz') or contains(@bar, 'xyz')]",//substring match
            'div[bar*="baz&xyz"]' => "//div[contains(@bar, 'baz') and contains(@bar, 'xyz')]",//substring match

            'div[=baz]'       => "//div[text()='baz']",//exact match
            'div[="baz"]'     => "//div[text()='baz']",//exact match
            'div[="baz|xyz"]' => "//div[text()='baz' or text()='xyz']",//exact match
            'div[="baz&xyz"]' => "//div[text()='baz' and text()='xyz']",//exact match

            'div[~=baz]'       => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ')]",//word match
            'div[~="baz"]'     => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ')]",//word match
            'div[~="baz|xyz"]' => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ') or contains(concat(' ', normalize-space(text()), ' '), ' xyz ')]",
            //word match
            'div[~="baz&xyz"]' => "//div[contains(concat(' ', normalize-space(text()), ' '), ' baz ') and contains(concat(' ', normalize-space(text()), ' '), ' xyz ')]",
            //word match

            'div[^=baz]'       => "//div[starts-with(text(), 'baz')]",//starts with
            'div[^="baz"]'     => "//div[starts-with(text(), 'baz')]",//starts with
            'div[^="baz|xyz"]' => "//div[starts-with(text(), 'baz') or starts-with(text(), 'xyz')]",//starts with
            'div[^="baz&xyz"]' => "//div[starts-with(text(), 'baz') and starts-with(text(), 'xyz')]",//starts with

            'div[$=baz]'       => "//div[ends-with(text(), 'baz')]",//ends with
            'div[$="baz"]'     => "//div[ends-with(text(), 'baz')]",//ends with
            'div[$="baz|xyz"]' => "//div[ends-with(text(), 'baz') or ends-with(text(), 'xyz')]",//ends with
            'div[$="baz&xyz"]' => "//div[ends-with(text(), 'baz') and ends-with(text(), 'xyz')]",//ends with

            'div[*=baz]'         => "//div[contains(text(), 'baz')]",//substring match
            'div[*="baz"]'       => "//div[contains(text(), 'baz')]",//substring match
            'div[*="baz|xyz"]'   => "//div[contains(text(), 'baz') or contains(text(), 'xyz')]",//substring match
            'div[*="baz&xyz"]'   => "//div[contains(text(), 'baz') and contains(text(), 'xyz')]",//substring match

            //direct descendents
            'div > span'         => '//div/span',

            //descendents
            'div .foo span #one' => "//div//*[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]//span//*[@id='one']|//div[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]//span//*[@id='one']",

            'foo|bar'          => '//foo|bar',

            //https://stackoverflow.com/questions/1604471/how-can-i-find-an-element-by-css-class-with-xpath
            'div>.foo'         => "//div/[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]",
            'div > .foo'       => "//div/[contains(concat(' ', normalize-space(@class), ' '), ' foo ')]",
            'div>.foo.bar'     => "//div/[contains(concat(' ', normalize-space(@class), ' '), ' foo ')][contains(concat(' ', normalize-space(@class), ' '), ' bar ')]",
            'div.bar span'     => "//div[contains(concat(' ', normalize-space(@class), ' '), ' bar ')]//span",
            'div > p.first'    => "//div/p[contains(concat(' ', normalize-space(@class), ' '), ' first ')]",
            'a[rel="include"]' => "//a[@rel='include']",
            "a[rel='include']" => "//a[@rel='include']",
            'a[rel=include]'   => "//a[@rel='include']",

            'p:first' => '//p[first()]',
            'p:last'  => '//p[last()]',
            'p:even'  => '//p[position() mod 2 = 0]',
            'p:odd'   => '//p[position() mod 2 = 1]',

            'p:eq(0)'  => '//p[1]',
            'p:eq(1)'  => '//p[2]',
            'p:eq(-1)' => '//p[last()-1]',

            'p:gt(0)'  => '//p[position()>1]',
            'p:gt(1)'  => '//p[position()>2]',
            'p:gt(-1)' => '//p[position()>last()-1]',

            'p:lt(0)'  => '//p[position()<1]',
            'p:lt(1)'  => '//p[position()<2]',
            'p:lt(-1)' => '//p[position()<last()-1]',

            ':not(li[href="#"])' => "//not(li[@href='#'])",

            ':header'              => '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]',
            ':contains("manaphp")' => "//[contains(.,'manaphp')]",
            ':empty'               => '//[not(* or text())]',

            'p:only-child'  => '//p[last()=1]',
            'p:first-child' => '//p[position()=1]',
            'p:last-child'  => '//p[position()=last()]'
        ];

        foreach ($css_xpaths as $css => $xpath) {
            $this->assertEquals(
                $xpath, $cssToXpath->transform($css),
                json_encode(
                    ['css' => $css, 'xpath' => $xpath],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
                )
            );
        }
    }
}