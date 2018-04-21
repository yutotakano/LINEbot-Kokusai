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
   * Get messages from /student/ib/messages as JSON.
   * 
   * @since 0.1
   */
  public function get() {

    $this->getMessagesRecursive();

    return $this->messages;

  }

  private function getMessagesRecursive($page = 1) {

    $response = $this->client->request(
      'GET',
      '/student/ib/messages/page/' . (string)$page,
      [
        'cookies' => $this->session
      ]
    );

    $html = HtmlDomParser::str_get_html($response->getBody());

    $messages = $html->find('main > .content-wrapper > .content-block > .message');

    $this->messages = array_merge($this->messages, $this->parseMessages($messages));

    $next_button = $html->find('main > .content-wrapper > .pagination li.next', 0);

    // The last page has the next button, but it's disabled
    if(strpos($next_button->class, 'disabled') === false) {
      
      // Free RAM and collect garbage
      $html->clear();
      gc_collect_cycles();

      // Recursive call for the next page
      $this->getMessagesRecursive($page + 1);

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
