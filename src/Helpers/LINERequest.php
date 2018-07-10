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
use \Exception;

error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * Core file for sending requests to LINE's API.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class LINERequest
{

  /**
   * Contains the request HTTP method.
   * @var String
   */
  private $method;

  /**
   * Contains the full URL to the API call.
   * @var String
   */
  private $url;

  /**
   * Contains the array POST data.
   * @var Array
   */
  private $post_data;

  /**
   * Prepare the sending.
   * 
   * @param String $method HTTP Method for sending to the API
   * @param String $url The relative URL to the API
   * @param Array $post_data The POST data encoded into a string
   * @since 0.1
   */
  public function prepare($method, $url, $post_data) {
    $this->method = $method;
    $this->url = $url;
    $this->post_data = $post_data;
  }

  /**
   * Send the prepared request.
   * 
   * @since 0.1
   */
  public function send() {

    if(!$this->url) {
      return false;
    }
    $client = new GuzzleHttp\Client([
      'base_uri' => 'https://api.line.me/v2/bot/'
    ]);
    $send_data = [
      'headers' => [
        'Authorization' => 'Bearer uBgDPwP+JjSKi9OahJu4yWJffmTqdhSSKwnOewXu4j/B2RgxKceO4OzAxRPedfTmoeWdKmxHweSg491JKJeXbFxaKwe58FiaMc5LAdqT3+siSFCg9PdIOhuh53TFGSOeixHMhe5y6i0imDdcSUKZ0wdB04t89/1O/w1cDnyilFU=',
        'Content-Type' => 'application/json'
      ]
    ];
    if($this->method === 'POST') {
      $send_data['json'] = $this->post_data;
    }
    try {

      $response = $client->request(
        $this->method,
        $this->url,
        $send_data
      );
    
      echo 'Response: ' . $response->getStatusCode() . PHP_EOL;

    } catch (Exception $e) {
      echo 'fail';
      print_r($send_data);
      echo (string)$e;
    }
  }

}