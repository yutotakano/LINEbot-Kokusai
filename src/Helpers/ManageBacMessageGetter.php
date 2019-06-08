<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
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
   * Will contain the user agent string for this session, randomly selected from the config.
   * @var String
   */
  private $user_agent;

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
   * @param String $user_agent The user agent for this session
   * @param String $csrf_token Pass in POST requests
   * @param CookieJar $session Cookie Jar for Guzzle requests
   * @param GuzzleHttp\Client $client Client to always use for this session
   * @since 0.1
   */
  public function __construct($user_agent, $csrf_token, $session, $client) {

    $this->user_agent = $user_agent;
    $this->csrf_token = $csrf_token;
    $this->session = $session;
    $this->client = $client;

  }

  /**
   * Get messages from /student/ib/discussions as an associative array.
   * 
   * @since 0.1
   */
  public function getAll() {

    $this->getMessagesRecursive('/student/ib/discussions');

    return $this->messages;

  }  

  /**
   * Get messages from /student/classes/10901407/discussions as an associative array.
   * Japanese A: Literature SL
   * 
   * @since 0.1
   */
  public function getJASL() {

    $this->getMessagesRecursive('/student/classes/10901407/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901409/discussions as an associative array.
   * English A: Language and Literature SL
   * 
   * @since 0.1
   */
  public function getEASL() {

    $this->getMessagesRecursive('/student/classes/10901409/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901414/discussions as an associative array.
   * History
   * 
   * @since 0.1
   */
  public function getHSL() {

    $this->getMessagesRecursive('/student/classes/10901414/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901417/discussions as an associative array.
   * Chemistry HL
   * 
   * @since 0.1
   */
  public function getCHL() {

    $this->getMessagesRecursive('/student/classes/10901417/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901419/discussions as an associative array.
   * Physics
   * 
   * @since 0.1
   */
  public function getPHL() {

    $this->getMessagesRecursive('/student/classes/10901419/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901421/discussions as an associative array.
   * Mathematics HL
   * 
   * @since 0.1
   */
  public function getMHL() {

    $this->getMessagesRecursive('/student/classes/10901421/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901422/discussions as an associative array.
   * Theory of Knowledge
   * 
   * @since 0.1
   */
  public function getTOK() {

    $this->getMessagesRecursive('/student/classes/10901422/discussions');

    return $this->messages;

  }

  /**
   * Get messages from /student/groups/10902178/discussions as an associative array.
   * Extended Essay Group
   * 
   * @since 0.1 
   */
  public function getEE() {

    $this->getMessagesRecursive('/student/groups/10902178/discussions');

    return $this->messages;
    
  }

  /**
   * Get messages from /student/groups/10538278/discussions as an associative array.
   * Career & College Counseling DP 2
   * 
   * @since 0.1 
   */
  public function getCC() {

    $this->getMessagesRecursive('/student/groups/10538278/discussions');

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

    try {
      $response = $this->client->request(
        'GET',
        $url . '?page=' . (string)$page,
        [
          'headers' => [
            'User-Agent' => $this->user_agent
          ],
          'cookies' => $this->session
        ]
      );
    } catch (GuzzleHttp\Exception\ClientException $e) {
      echo 'We have encountered an error in getMessagesRecursive(). Maybe a user-agent block or captcha?' . PHP_EOL;
      echo 'The following is the response:' . PHP_EOL;
      print_r($e->getResponse()->getBody()->getContents());
      return;
    }

    $html = HtmlDomParser::str_get_html($response->getBody());

    $messages = $html->find('main > .content-wrapper > .content-block > .discussion');

    $this->messages = array_merge($this->messages, $this->parseMessages($messages));

    $next_button = $html->find('main > .content-wrapper > .pagination li.next', 0);

    // Free RAM and collect garbage
    $html->clear();
    gc_collect_cycles();

    if(!$next_button) return;

    // The last page has the next button, but it's disabled
    if(strpos($next_button->class, 'disabled') === false) {

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

      if(($message->find('.label-danger text', 0)->innertext ?? '') === 'Only Visible for Teachers') continue;

      $paragraphs = [];
      foreach($message->find('.body .fix-body-margins text') as $paragraph) {
        $paragraphs[] = str_replace("\n", '<br>', $paragraph->innertext);
      }

      array_push($array, [
        'id' => substr($message->id, 11 ),
        'title' => $message->find('.body .title a', 0)->innertext,
        'author' => $message->find('.header strong', 0)->innertext,
        'category' => $message->find('.header em', 0)->innertext ?? null,
        'body' => html_entity_decode(implode('<br><br>', $paragraphs))
      ]);

    }

    return $array;

  }

}
