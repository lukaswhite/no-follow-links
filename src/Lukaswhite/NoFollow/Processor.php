<?php

namespace Lukaswhite\NoFollow;

use DOMDocument;
use Exception;

class Processor
{
 
  /**
   * Allows you to override the current host. Otherwise, this is derived from 
   * $_SERVER['HTTP_HOST']
   * 
   * @param string $host
   */
  public function setCurrentHost($host)
  {
    $this->currentHost = $host;
  }

  /**
   * Returns the current host. If it's been set specifically then that's what's returned,
   * otherwise it's taken from $_SERVER.
   * 
   * @return string
   */
  private function getCurrentHost()
  {
    if ($this->currentHost) {
      return $this->currentHost;
    }
    return strtok($_SERVER['HTTP_HOST'], ':');
  }

  /**
   * Adds rel=nofollow to links in a string of HTML.
   * 
   * @param string    $html
   * @param array     $whitelist       An optional array of hostnames to skip
   * @param boolean   $ignoreRelative  Whether to ignore relative links (e.g. /about-us)
   * @return string 
   * @throws Exception
   */
  public function addLinks($html, $whitelist = array(), $ignoreRelative = true)
  {
    // Create a OMDocument, which we can traverse and then modify
    $dom = new DOMDocument();

    // Remove redundant whitespace
    $dom->preserveWhitespace = FALSE;

    // Populate the DOMDocument with the provided HTML string
    $dom->loadHTML($html);

    // Grab all of the <a> tags.
    $a = $dom->getElementsByTagName('a');

    // Check that the HTML has been parsed okay
    if ($a === FALSE) {
      throw new Exception('Could not parse the provided HTML.');
    }

    $currentHost = $this->getCurrentHost();

    foreach($a as $anchor) {

      // Grab the href attribute from the link
      $href = $anchor->attributes->getNamedItem('href')->nodeValue;

      // Exract the relevant parts
      $url = parse_url($href);      

      // If this is a relative link and we're ignoring them, skip
      if ( ( !isset($url['host']) ) && $ignoreRelative ) {
        continue;
      }

      // If this is a full URL to the current host, skip
      if ( isset($url['host']) && ( $url['host'] == $currentHost ) ) {        
        continue;      
      }

      // If the host is set and it's whitelisted, skip
      if ( isset($url['host']) && in_array($url['host'], $whitelist) ) {
        continue;
      }

      $oldRelAtt = $anchor->attributes->getNamedItem('rel');

      if ($oldRelAtt == NULL) {
        $newRel = 'nofollow';
      } else {
        $oldRel = $oldRelAtt->nodeValue;
        $oldRel = explode(' ', $oldRel);
        if (in_array('nofollow', $oldRel)) {
            continue;
        }
        $oldRel[] = 'nofollow';
        $newRel = implode($oldRel,  ' ');
      }

      $newRelAtt = $dom->createAttribute('rel');
      $noFollowNode = $dom->createTextNode($newRel);
      $newRelAtt->appendChild($noFollowNode);
      $anchor->appendChild($newRelAtt);
    }

    return $dom->saveHTML();
  } 

}