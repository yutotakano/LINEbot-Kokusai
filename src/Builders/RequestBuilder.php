<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Builders;

use KokusaiIBLine\Components\Request;
use KokusaiIBLine\Helpers\ValidateRequest;

/**
 * Construct a Request object from POST data.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class RequestBuilder
{

  /**
   * Instances of constructed Request.
   * @var Array
   */
  private $requests = [];

  /**
   * Receivers to route the request to.
   * @var Array
   */
  private $routes = [
    'message/text' => 'TextMessageReceiver',
    'postback' => 'PostbackReceiver' /*,
    'message/image' => 'ImageMessageReceiver',
    'message/video' => 'VideoMessageReceiver',
    'message/audio' => 'AudioMessageReceiver',
    'message/file' => 'FileMessageReceiver',
    'message/location' => 'LocationMessageReceiver',
    'message/sticker' => 'StickerMessageReceiver',
    'follow' => 'FollowReceiver',
    'unfollow' => 'UnfollowReceiver',
    'join' => 'JoinReceiver',
    'leave' => 'LeaveReceiver'*/
  ];

  /**
   * Build Request, only if the request format fits into one of the predefined $routes
   * 
   * @since 0.1
   */
  public function build() {

    $post_data = file_get_contents('php://input');
    $validation = ValidateRequest::validate($post_data);
    if(!$validation) {
      echo 'Wrong validation.';
      return;
    }

    $json = json_decode($post_data, true);
    if(!$json) {
      echo 'Invalid JSON payload.';
      return;
    }

    foreach($json['events'] as $event) {
      if($event['type'] === 'message') {
        $identifier = $event['type'] . '/' . $event['message']['type'];
      } else {
        $identifier = $event['type'];
      }

      if(!isset($this->routes[$identifier])) {
        echo 'Receiver ' . $identifier . ' could not be found.';
        return;
      }

      $request = new Request($this->routes[$identifier], $event);
      array_push($this->requests, $request);
    }

  }

  /**
   * Retrieve the built Requests.
   * 
   * @return Array
   * @since 0.1
   */
  public function getRequests() {

    return $this->requests;
    
  }
  
}