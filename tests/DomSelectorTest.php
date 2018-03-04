<?php
namespace Tests;

use ManaPHP\Dom\Selector;

class DomSelectorTest extends \PHPUnit_Framework_TestCase
{
    public function test_usage()
    {
        $body = '<html><body><span>good</span></body></html>';
        $selector = new Selector($body);

        $this->assertEquals(['good'], $selector->xpath('//span/text()')->extract());

        $body = <<<STR
<html>
 <head>
  <base href='http://example.com/' />
  <title>Example website</title>
 </head>
 <body>
  <div id='images'>
   <a href='image1.html'>Name: My image 1 <br /><img src='image1_thumb.jpg' /></a>
   <a href='image2.html'>Name: My image 2 <br /><img src='image2_thumb.jpg' /></a>
   <a href='image3.html'>Name: My image 3 <br /><img src='image3_thumb.jpg' /></a>
   <a href='image4.html'>Name: My image 4 <br /><img src='image4_thumb.jpg' /></a>
   <a href='image5.html'>Name: My image 5 <br /><img src='image5_thumb.jpg' /></a>
  </div>
 </body>
</html>
STR;

        $selector = new Selector($body);

        $this->assertEquals(
            ['image1_thumb.jpg', 'image2_thumb.jpg', 'image3_thumb.jpg', 'image4_thumb.jpg', 'image5_thumb.jpg'],
            $selector->css('img')->xpath('@src')->extract());

        $this->assertEquals(['Example website'], $selector->xpath('//title/text()')->extract());
        $this->assertEquals('Example website', $selector->xpath('//title/text()')->extract_first());
        $this->assertNull($selector->xpath('//div[@id="not-exists"]/text()')->extract_first());
        $this->assertEquals('not-found', $selector->xpath('//div[@id="not-exists"]/text()')->extract_first('not-found'));

        //   $this->assertEquals('' . $selector->css('title::text') . extract());

        $this->assertEquals(['http://example.com/'], $selector->xpath('//base/@href')->extract());
        // $this->assertEquals(['http://example.com/'], $selector->css('base::attr(href)')->extract());

        $this->assertEquals(
            ['image1.html', 'image2.html', 'image3.html', 'image4.html', 'image5.html'],
            $selector->xpath('//a[contains(@href, "image")]/@href')->extract());

//        $this->assertEquals(
//            ['image1.html', 'image2.html', 'image3.html', 'image4.html', 'image5.html'],
//            $selector->xpath('a[href*=image]::attr(href)')->extract());

        $this->assertEquals(
            ['image1_thumb.jpg', 'image2_thumb.jpg', 'image3_thumb.jpg', 'image4_thumb.jpg', 'image5_thumb.jpg'],
            $selector->xpath('//a[contains(@href, "image")]/img/@src')->extract());

//        $this->assertEquals(
//            ['image1_thumb.jpg', 'image2_thumb.jpg', 'image3_thumb.jpg', 'image4_thumb.jpg', 'image5_thumb.jpg'],
//            $selector->css('a[href*=image] img::attr(src)')->extract());

//        $this->assertEquals(
//            [
//                '<a href="image1.html">Name: My image 1 <br><img src="image1_thumb.jpg"></a>',
//                '<a href="image2.html">Name: My image 2 <br><img src="image2_thumb.jpg"></a>',
//                '<a href="image3.html">Name: My image 3 <br><img src="image3_thumb.jpg"></a>',
//                '<a href="image4.html">Name: My image 4 <br><img src="image4_thumb.jpg"></a>',
//                '<a href="image5.html">Name: My image 5 <br><img src="image5_thumb.jpg"></a>'
//            ],
//            $selector->xpath('//a[contains(@href, "image")]')->extract());

        $this->assertEquals(
            ['My image 1 ', 'My image 2 ', 'My image 3 ', 'My image 4 ', 'My image 5 '],
            $selector->xpath('//a[contains(@href, "image")]/text()')->re('#Name:\s*(.*)#'));

        $this->assertEquals(
            'My image 1 ',
            $selector->xpath('//a[contains(@href, "image")]/text()')->re_first('#Name:\s*(.*)#'));

        $this->assertEquals('Name: My image 1 ', $selector->xpath(['//div[@id=$val]/a/text()', 'val' => 'images'])->extract_first());

//      $this->assertEquals('Name: My image 1 ', $selector->xpath(['//div[count(a)=$cnt]/@id', 'cnt'=>5])->extract_first());

        $selector = new Selector('<a href="#">Click here to go to the <strong>Next Page</strong></a>');
        $this->assertEquals(['Click here to go to the ', 'Next Page'], $selector->xpath('//a//text()')->extract());
//      $this->assertEquals(['Click here to go to the '], $selector->xpath('string(//a[1]//text())')->extract());

        $selector = new Selector('<div class="hero shout"><time datetime="2014-07-23 19:00">Special date</time></div>');
        $this->assertEquals(['2014-07-23 19:00'], $selector->css('.shout')->xpath('./time/@datetime')->extract());

        $body = <<<STR
        <body>
<ul class="list">
    <li>1</li>
    <li>2</li>
    <li>3</li>
</ul>
<ul class="list">
    <li>4</li>
    <li>5</li>
    <li>6</li>
</ul>
</body>
STR;
        $selector = new Selector($body);

//      $this->assertEquals(['<li>1</li>', '<li>4</li>'], $selector->xpath('//li[1]')->extract());
//      $this->assertEquals(['<li>1</li>'], $selector->xpath('(//li)[1]')->extract());
//        $this->assertEquals(['<li>1</li>', '<li>4</li>'], $selector->xpath('//ul/li[1]')->extract());
        // $this->assertEquals(['<li>1</li>'],$selector->xpath('(//ul/li)[1]')->extract());
    }

    public function test_css()
    {
        $document = file_get_contents(__DIR__ . '/Dom/sample.html');
        $selector = new Selector($document);

        $this->assertInstanceOf('ManaPHP\Dom\SelectorList', $selector->css('.foo'));
        $this->assertEquals(['Item 1', 'Item 2', 'Item 3'], $selector->css('.foo')->text());

        $this->assertCount(1, $selector->css('.footerblock .last')->text());

     //   $this->assertCount(1, $selector->css('div[dojoType="FilteringSelect"]')->element());
    }
}