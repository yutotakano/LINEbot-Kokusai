<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Receivers;

use KokusaiIBLine\Receivers\TextMessageReceiver;

/**
 * Receiver for postback webhooks
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class PostbackReceiver
{

  /**
   * Constructor and basic handler.
   * 
   * @param Array $event The array-parsed JSON payload of the event.
   * { "type":"postback", "replyToken":"b60d432864f44d079f6d8efe86cf404b", "source":{ "userId":"U91eeaf62d...", "type":"user" }, "timestamp":1513669370317, "postback":{ "data":"storeId=12345", "params":{ "datetime":"2017-12-25T01:00" } } }
   * @since 0.1
   */
  public function __construct($event) {

    $data = json_decode($event['postback']['data'], true);

    if(!$data) return false;

    if($data['action'] === 'subscribe_class') {
      // Forward to TextMessageReceiver
      new TextMessageReceiver([
        'type' => 'message',
        'replyToken' => $event['replyToken'],
        'source' => $event['source'],
        'timestamp' => $event['timestamp'],
        'message' => [
          'type' => 'text',
          'text' => 'subscribe to ' . $data['data']['class_code'] . ' messages'
        ]
      ]);

    }

  }

}