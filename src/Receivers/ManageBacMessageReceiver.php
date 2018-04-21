<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 0.1
 */

namespace KokusaiIBLine\Receivers;

use KokusaiIBLine\Helpers\LINERequest;

/**
 * Check if there are new IB Messages and if yes send them to the group chats
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class ManageBacMessageReceiver
{

  /**
   * Constructor and basic handler.
   * 
   * @param Array $current_messages The array-parsed messages in the IB Grade board.
   * @since 0.1
   */
  public function __construct($current_messages) {

    // Create cache folder if not exist
    if(!file_exists(__DIR__ . '/../../app/cache')) {
      mkdir(__DIR__ . '/../../app/cache', 0777, true);
    }

    if(!file_exists(__DIR__ . '/../../app/cache/IBMessages.json')) {
      
      $previous_messages = $current_messages;
    
    } else { 

      $previous_messages = @json_decode(
        file_get_contents(__DIR__ . '/../../app/cache/IBMessages.json')
      , true) ?? [];

    }

    file_put_contents(__DIR__ . '/../../app/cache/IBMessages.json', json_encode($current_messages));

    // Use a custom diff function (udiff) instead of array_diff
    // because array_diff does not work on multidimensional arrays
    // https://stackoverflow.com/questions/11821680/array-diff-with-multidimensional-arrays
    $new_messages = array_udiff($current_messages, $previous_messages, function($a, $b) {
      // Spaceship operator (returns -1 if right is greater, 1 if left is greater, 0 if equal)
      return $a['id'] <=> $b['id'];
    });

    
    
    // if(!empty($reply['messages'])) {
    //   $request = new LINERequest();
    //   $request->prepare('POST', 'message/reply', $reply);
    //   $request->send();
    // }

  }

}