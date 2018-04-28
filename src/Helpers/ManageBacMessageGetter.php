<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
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
 * Construct a ManageBac session and grab the messages for the IB group.
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
   * Constructor function to create a Guzzle client to use later.
   * 
   * @since 0.1
   */
  public function __construct() {
    
    $this->client = new GuzzleHttp\Client([
      'base_uri' => 'https://' . MANAGEBAC_DOMAIN . '.managebac.com/'
    ]);

  }

  /**
   * Prepare, login, and store the cookies' jar and csrf_token for ManageBac requests.
   * 
   * @since 0.1
   */
  public function prepare() {

    $cookieJar = new GuzzleHttp\Cookie\CookieJar;

    $loginResponse = $this->client->request(
      'GET',
      'login',
      [
        'cookies' => $cookieJar,
        'allow_redirects' => false
      ]
    );

    if($loginResponse->getStatusCode() === 404) {
      throw new Exception('ManageBac does not exist on this domain.');
    }

    $doc = new DOMDocument();
    @$doc->loadHTML($loginResponse->getBody());
    $nodes = $doc->getElementsByTagName("meta");
    
    // Get csrf_token, required for logging in
    for($i = 0; $i < $nodes->length; $i++) {
      $meta = $nodes->item($i);
      if($meta->getAttribute("name") == "csrf-token") {
        $csrf_token = $meta->getAttribute("content");
      }
    }


    $sessionResponse = $this->client->request(
      'POST',
      'sessions',
      [
        'cookies' => $cookieJar,
        'allow_redirects' => false,
        'form_params' => [
          'login' => MANAGEBAC_LOGIN,
          'password' => MANAGEBAC_PASSWORD,
          'remember_me' => '0',
          'commit' => 'Sign-in',
          'utf' => '%E2%9C%93',
          'authenticity_token' => $csrf_token
        ]
      ]
    );

    if($sessionResponse->getStatusCode() === 200) {
      throw new Exception('Invalid ManageBac credentials provided.');
    }

    $final_location = $sessionResponse->getHeader('Location')[0];

    if(strpos($final_location, '/student') !== false) {
      
      $this->csrf_token = $csrf_token;
      $this->session = $cookieJar;

    } else {
      
      throw new Exception('Invalid credentials.');

    }

  }

  /**
   * 
   */

  /**
   * Get messages from /student/ib/messages as JSON.
   * 
   * @since 0.1
   */
  public function getAll() {

    $this->getMessagesRecursive('/student/ib/messages');

    return $this->messages;

  }  

  /**
   * Get messages from /student/classes/10901407/messages as JSON.
   * Japanese A: Literature SL
   * 
   * @since 0.1
   */
  public function getJASL() {

    $this->getMessagesRecursive('/student/classes/10901407/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901409/messages as JSON.
   * English A: Language and Literature SL
   * 
   * @since 0.1
   */
  public function getEASL() {

    $this->getMessagesRecursive('/student/classes/10901409/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901414/messages as JSON.
   * History
   * 
   * @since 0.1
   */
  public function getHSL() {

    $this->getMessagesRecursive('/student/classes/10901414/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901417/messages as JSON.
   * Chemistry HL
   * 
   * @since 0.1
   */
  public function getCHL() {

    $this->getMessagesRecursive('/student/classes/10901417/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901419/messages as JSON.
   * Physics
   * 
   * @since 0.1
   */
  public function getPHL() {

    $this->getMessagesRecursive('/student/classes/10901419/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901421/messages as JSON.
   * Mathematics HL
   * 
   * @since 0.1
   */
  public function getMHL() {

    $this->getMessagesRecursive('/student/classes/10901421/messages');

    return $this->messages;

  }

  /**
   * Get messages from /student/classes/10901422/messages as JSON.
   * Theory of Knowledge
   * 
   * @since 0.1
   */
  public function getTOK() {

    $this->getMessagesRecursive('/student/classes/10901422/messages');

    return $this->messages;

  }

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

  private function parseMessages($messages, $array = []) {

    foreach($messages as $message) {

      $paragraphs = [];
      foreach($message->find('.body p') as $paragraph) {
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
