<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Components;

/**
 * Class Request
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class Request
{
   
  /**
   * Contains the receiver class name.
   * @var String
   */
  private $receiver;


  /**
   * Contains the array parsed JSON payload of the event.
   * @var Array
   */
  private $event;
  
  /**
   * Constructor to build Request.
   * 
   * @param String $receiver The receiver class name for the webhook.
   * @param Array $event The array-parsed JSON payload for the event.
   * @since 0.1
   */
  public function __construct(
    String $receiver,
    Array $event
  ) {
    $this->receiver = $receiver;
    $this->event = $event;
  }

  /** 
   * @return String
   * @since 0.1
   */
  public function getReceiver() {
    return $this->receiver;
  }

  /** 
   * @return Array
   * @since 0.1
   */
  public function getEvent() {
    return $this->event;
  }

}