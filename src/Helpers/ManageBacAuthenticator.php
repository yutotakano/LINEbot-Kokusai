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

/**
 * Construct a ManageBac session.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class ManageBacAuthenticator
{

  /**
   * Will contain the ManageBac csrf token to pass in requests.
   * @var String
   */
  public $csrf_token;

  /**
   * Will contain the ManageBac Guzzle cookie jar to pass in requests.
   * @var CookieJar
   */
  public $session;

  /**
   * Will contain the Guzzle client to use for sending requests to ManageBac.
   * @var GuzzleHttp\Client
   */
  public $client;

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
   * Login, and store the cookies' jar and csrf_token for ManageBac requests.
   * 
   * @since 0.1
   */
  public function authenticate() {

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
    $nodes = $doc->getElementsByTagName('meta');
    
    // Get csrf_token, required for logging in
    for($i = 0; $i < $nodes->length; $i++) {
      $meta = $nodes->item($i);
      if($meta->getAttribute('name') == 'csrf-token') {
        $csrf_token = $meta->getAttribute('content');
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
      return true;

    } else {
      
      throw new Exception('Invalid credentials.');

    }

  }

}
