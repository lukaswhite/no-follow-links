<?php

use Mockery as m;
use Laravel\Cashier\Invoice;

class NoFollowTest extends PHPUnit_Framework_TestCase
{
  
  /**
   * Stores the HTML used for testing
   * 
   * @var string
   */
  private $html;

  /**
   * Test our utility function, otherwise the whole premise of the test
   * is invalid.
   */
  public function testExtractLinks()
  {
    // We know the contents of the included HTML, so we'll use that
    $links = $this->extractLinks($this->loadHtml());
    
    // Check that $links is an array...
    $this->assertInternalType('array', $links);

    // ...there are six links, check that
    $this->assertEquals(6, count($links));

    // Now check the rel attribute for each one; this should reflect what's
    // in the test HTML, because we haven't actually *done* anything

    $this->assertNull($links['/about']);
    $this->assertNull($links['http://www.bbc.co.uk']);
    // This has a rel attribute
    $this->assertEquals('me', $links['http://www.google.com']);
    // This already has a nofollow attribute
    $this->assertEquals('nofollow', $links['http://php.net']);    
    $this->assertNull($links['http://example.com/some-page']);    
    $this->assertNull($links['http://example.org/another-page']);
  }

  public function testAll()
  {
    $processor = new Lukaswhite\NoFollow\Processor();    
    $processor->setCurrentHost('localhost');    
    $output = $processor->addLinks($this->loadHtml());
    $links = $this->extractLinks($output);
    
    // Internal link
    $this->assertNull($links['/about']);
    // External link
    $this->assertEquals('nofollow', $links['http://www.bbc.co.uk']);
    // External link with existing rel attribute
    $this->assertEquals('me nofollow', $links['http://www.google.com']);
    // External link which already has a nofollow attribute
    $this->assertEquals('nofollow', $links['http://php.net']);
    // External link, not whitelisted
    $this->assertEquals('nofollow', $links['http://example.com/some-page']);
    // External link, not whitelisted
    $this->assertEquals('nofollow', $links['http://example.org/another-page']);
    
  }

  public function testIgnoreCurrentHost()
  {
    $processor = new Lukaswhite\NoFollow\Processor();    
    $processor->setCurrentHost('example.com');    
    $output = $processor->addLinks($this->loadHtml(), [], true);
    $links = $this->extractLinks($output);
    
    // External link, set as current, so nofollow should not have been set
    $this->assertNull($links['http://example.com/some-page']);
    
  }

  public function testDontIgnoreRelative()
  {
    $processor = new Lukaswhite\NoFollow\Processor();    
    $processor->setCurrentHost('example.com');    
    $output = $processor->addLinks($this->loadHtml(), [], false);
    $links = $this->extractLinks($output);
    
    // We've stated we don't want to ignore relative links, so this should be nofollow
    $this->assertEquals('nofollow', $links['/about']);
    
  }

  public function testWithWhitelist()
  {
    $processor = new Lukaswhite\NoFollow\Processor();  
    $processor->setCurrentHost('localhost');          
    $output = $processor->addLinks(
      $this->loadHtml(), 
      [
        'www.bbc.co.uk',
        'www.google.com', 
        'example.com',
      ]
      , true);
    $links = $this->extractLinks($output);

    // this is whitelisted
    $this->assertNull($links['http://www.bbc.co.uk']);
    // Ensure the existing rel attribute hasn't been removed
    $this->assertEquals('me', $links['http://www.google.com']);
    // this is also whitelisted
    $this->assertNull($links['http://example.com/some-page']);

  }

  /**
   * This time we'll pass some HTML that doesn't contain any links.
   *
   * We're configured to throw an exception if any PHP warnings are thrown up.
   */
  public function testWithNoLinks()
  {
    $processor = new Lukaswhite\NoFollow\Processor();  
    $processor->setCurrentHost('localhost');          
    $output = $processor->addLinks('<p>Just a paragraph</p>');
    $links = $this->extractLinks($output);

    // Check that $links is an array...
    $this->assertInternalType('array', $links);

    // ..which is empty
    $this->assertEmpty($links);
    $this->assertEquals(0, count($links));
  }

  /**
   * This time, the string provided will be plain text
   *
   * We're configured to throw an exception if any PHP warnings are thrown up.
   */
  public function testPlainText()
  {
    $processor = new Lukaswhite\NoFollow\Processor();  
    $processor->setCurrentHost('localhost');          
    $output = $processor->addLinks('I am just text');
    $links = $this->extractLinks($output);

    // Check that $links is an array...
    $this->assertInternalType('array', $links);

    // ..which is empty
    $this->assertEmpty($links);
    $this->assertEquals(0, count($links));
  }

  /**
   * Test with invalid HTML
   *
   * We're configured to throw an exception if any PHP warnings are thrown up.
   */
  public function testInvalidHtml()
  {
    $processor = new Lukaswhite\NoFollow\Processor();  
    $processor->setCurrentHost('localhost');          
    $output = $processor->addLinks('<p>I am a paragraph that is unfin...');
    $links = $this->extractLinks($output);

    // Check that $links is an array...
    $this->assertInternalType('array', $links);

    // ..which is empty
    $this->assertEmpty($links);
    $this->assertEquals(0, count($links));
  }

  /**
   * Test with invalid HTML, that also has a link
   *
   * We're configured to throw an exception if any PHP warnings are thrown up.
   */
  public function testInvalidHtmlWithLink()
  {
    $processor = new Lukaswhite\NoFollow\Processor();  
    $processor->setCurrentHost('localhost');          
    $output = $processor->addLinks('<p>I am a paragraph <a href="http://www.bbc.co.uk">with a link</a> that is unfin...');
    $links = $this->extractLinks($output);

    $this->assertEquals('nofollow', $links['http://www.bbc.co.uk']);
  }

  /**
   * Loads the HTML for testing
   * 
   * @return string
   */
  private function loadHtml()
  {
    if ($this->html) {
      return $this->html;
    }
    
    $this->html = file_get_contents(__DIR__ . '/fixtures/index.html');
    return $this->html;
  }
  
  /**
   * Helper method to extract the links from some HTML, which we
   * can use for testing.
   *
   * Resulting array is keyed by URL, values are the contents of the rel attribute
   * or NULL if they are not set
   * 
   * @param  string $html
   * @return array
   */
  private function extractLinks($html)
  {
    
    $links = [];

    $dom = new DOMDocument();
    
    $dom->preserveWhitespace = FALSE;

    $dom->loadHTML($html);

    // Grab all of the <a> tags.
    $a = $dom->getElementsByTagName('a');

    foreach($a as $anchor) {

      // Grab the href attribute from the link
      $href = $anchor->attributes->getNamedItem('href')->nodeValue;

      // Exract the relevant parts
      $url = parse_url($href);

      $relAttr = $anchor->attributes->getNamedItem('rel');

      if ($relAttr) {
        $links[$href] = $relAttr->nodeValue;
      } else {
        $links[$href] = null;
      }

    }

    return $links;

  }

}
