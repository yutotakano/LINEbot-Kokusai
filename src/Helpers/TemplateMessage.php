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
class TemplateMessage
{

  /**
   * Contains the array data to be sent to LINE.
   * @var Array
   */
  public $data = [
    'type' => 'template',
    'template' => []
  ];

  public function __construct($type, $data, $alt_text) {

    $this->data['altText'] = $alt_text; 
    $this->data['template']['type'] = $type;
    $this->data['template'] = array_merge_recursive($this->data['template'], $data);
    
  }

}
