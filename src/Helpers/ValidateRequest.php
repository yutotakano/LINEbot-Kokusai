<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Helpers;

/**
 * Construct a Request object from POST data.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class ValidateRequest
{
  /**
   * Checks for the validity of the POST data through X-Line-Signature headers
   * 
   * @since 0.1
   * @param String $post_data The raw POST data for the request (can be empty)
   * @return Boolean
   */
  public static function validate($post_data) {

    $channel_secret = '410d22bf914c3a63f220f566213452f8';
    $hash = hash_hmac('sha256', $post_data, $channel_secret, true); // TODO: Set to true before production
    $signature = base64_encode($hash);
    if(!isset($_SERVER['HTTP_X_LINE_SIGNATURE']) || $signature !== $_SERVER['HTTP_X_LINE_SIGNATURE']) {
      return false;
    } else {
      return true;
    }
    
  }

}
