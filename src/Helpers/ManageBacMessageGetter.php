<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 0.1
 */

namespace KokusaiIBLine\Helpers;

use GuzzleHttp;
use DOMDocument;
use Exception;
use Sunra\PhpSimple\HtmlDomParser;

/**
 * Grab the messages for the IB group using the session obtained in ManageBacAuthenticator.
 * (should exist for any grade - checked with Sufasi)
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class ManageBacMessageGetter
{

  /**
   * Will contain the ManageBac csrf token to pass in requests.
   * @var String
   */
  private $csrf_token;

  /**
   * Will contain the ManageBac Guzzle cookie jar to pass in requests.
   * @var CookieJar
   */
  private $session;

  /**
   * Will contain the Guzzle client to use for sending requests to ManageBac.
   * @var GuzzleHttp\Client
   */
  private $client;

  /**
   * Will contain the messages as the recursive function gets them page by page.
   * @var Array
   */
  private $messages = [];

  /**
   * Fill in the required fields
   * 
   * @param String $csrf_token Pass in POST requests
   * @param CookieJar $session Cookie Jar for Guzzle requests
   * @param GuzzleHttp\Client $client Client to always use for this session
   * @since 0.1
   */
  public function __construct($csrf_token, $session, $client) {

    $this->csrf_token = $csrf_token;
    $this->session = $session;
    $this->client = $client;

  }

  /**
   * Get messages from /student/ib/messages as an associative array.
   * 
   * @since 0.1
   */
  public function getAll() {

    $this->getMessagesRecursive('/student/ib/messages');

    return $this->messages;

  }  

  /**
   * Get messages from /student/classes/10901407/messages as an associative array.
   * Japanese A: Literature SL
   * 
   * @since 0.1
   */
  public function getJASL() {

    $this->getMessagesRecursive('/student/classes/10901407/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901409/messages as an associative array.
   * English A: Language and Literature SL
   * 
   * @since 0.1
   */
  public function getEASL() {

    $this->getMessagesRecursive('/student/classes/10901409/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901414/messages as an associative array.
   * History
   * 
   * @since 0.1
   */
  public function getHSL() {

    $this->getMessagesRecursive('/student/classes/10901414/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901417/messages as an associative array.
   * Chemistry HL
   * 
   * @since 0.1
   */
  public function getCHL() {

    $this->getMessagesRecursive('/student/classes/10901417/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901419/messages as an associative array.
   * Physics
   * 
   * @since 0.1
   */
  public function getPHL() {

    $this->getMessagesRecursive('/student/classes/10901419/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901421/messages as an associative array.
   * Mathematics HL
   * 
   * @since 0.1
   */
  public function getMHL() {

    $this->getMessagesRecursive('/student/classes/10901421/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901422/messages as an associative array.
   * Theory of Knowledge
   * 
   * @since 0.1
   */
  public function getTOK() {

    $this->getMessagesRecursive('/student/classes/10901422/messages');

    return $this->messages;

  }

  /**
   * Recursively get the message elements on each page to pass into parseMessages()
   * 
   * @param String $url The base relative URL for the messages
   * @param Integer $page The page number
   * @since 0.1
   */
  private function getMessagesRecursive($url, $page = 1) {

    $response = $this->client->request(
      'GET',
      $url . '/page/' . (string)$page,
      [
        'cookies' => $this->session
      ]
    );

    $html = HtmlDomParser::str_get_html($response->getBody());

    $messages = $html->find('main > .content-wrapper > .content-block > .message');

    $this->messages = array_merge($this->messages, $this->parseMessages($messages));

    $next_button = $html->find('main > .content-wrapper > .pagination li.next', 0);

    if(!$next_button) return;

    // The last page has the next button, but it's disabled
    if(strpos($next_button->class, 'disabled') === false) {
      
      // Free RAM and collect garbage
      $html->clear();
      gc_collect_cycles();

      // Recursive call for the next page
      $this->getMessagesRecursive($url, $page + 1);

    }

  }

  /**
   * Parse the messages from HTML elements into an associative array and return it
   * 
   * @param Array $messages An array of HTML elements each containing a message
   * @param Array $array An array to push the results into and return it
   * @return Array
   * @since 0.1
   */
  private function parseMessages($messages, $array = []) {

    foreach($messages as $message) {

      $paragraphs = [];
      foreach($message->find('.body p') as $paragraph) {
        foreach($paragraph->find('a') as $link) {
          $link->outertext = $link->innertext . ' (' . $link->href . ')';
        }
        $paragraphs[] = $paragraph->innertext;
      }

      array_push($array, [
        'id' => substr($message->id, 8),
        'title' => $message->find('.body .title a', 0)->innertext,
        'body' => implode('<br><br>', $paragraphs)
      ]);

    }

    return $array;

  }

}
