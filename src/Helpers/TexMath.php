<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Helpers;

use KokusaiIBLine\Helpers\ImageMessage;
use Gregwar\Tex2png\Tex2png;
use GuzzleHttp;
use \Exception;

/**
 * Generates Text Messages.
 * 
 * @since 1.0.2
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class TexMath
{

  /**
   * Boolean indicating if a Latex equation is present in the text.
   * @var Boolean
   */
  public $present;

  /**
   * Contains the array of Latex math matches.
   * @var Array
   */
  public $matches = [];

  /**
   * Array of image messages that results from uploading the matches to Imgur.
   * @var Array
   */
  public $messages = [];

  public function __construct($text) {
    
    preg_match_all('/\$(.+)\$/', $text, $matches);
    if(count($matches[1]) === 0) {
      $this->present = false;
      return;
    }
    $this->present = true;
    $this->matches = $matches[1];

  }

  /**
   * Render an image for every match, and store the rendered file names
   */
  public function render() {
    foreach($this->matches as $match) {
      $a = Tex2png::create($match)->generate();
      $image = file_get_contents($a->file);
      if($image === '') return;

      $client = new GuzzleHttp\Client([
        'base_uri' => 'https://api.imgur.com/3/'
      ]);
      try {
        $response = $client->request('POST', 'image.json', [
          'multipart' => [
            [
              'name' => 'image',
              'contents' => $image
            ]
          ],
          'headers' => [
            'Authorization' => 'Client-ID ' . IMGUR_CLIENT_ID
          ]
        ]);
      } catch (GuzzleHttp\Exception\ClientException $e) {
        $response = $e->getResponse();
        $responseBodyAsString = $response->getBody()->getContents();
        echo 'Client error when uploading Tex Image: ' . $response->getStatusCode() . ' ' . $responseBodyAsString . PHP_EOL;
        return;
      } catch (GuzzleHttp\Exception\ServerException $e) {
        $response = $e->getResponse();
        $responseBodyAsString = $response->getBody()->getContents();
        echo 'Server error when uploading Tex Image: ' . $response->getStatusCode() . ' ' . $responseBodyAsString . PHP_EOL;
        return;
      }
      $this->messages[] = new ImageMessage(json_decode($response->getBody())->data->link);
    }

    
  }

}
