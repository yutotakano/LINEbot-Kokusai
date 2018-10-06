<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Helpers;

use \Exception;

/**
 * Generates Text Messages.
 * 
 * @since 1.0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class TextMessage
{

  /**
   * Contains the array data to be sent to LINE.
   * @var Array
   */
  public $data = [
    'type' => 'text'
  ];

  public function __construct($text) {

    $this->data['text'] = $text;

  }

}
