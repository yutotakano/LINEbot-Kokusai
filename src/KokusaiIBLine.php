<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 0.1
 */

namespace KokusaiIBLine;

use KokusaiIBLine\Helpers\ManageBacAuthenticator;
use KokusaiIBLine\Helpers\ManageBacMessageGetter;
use KokusaiIBLine\Receivers\ManageBacMessageReceiver;

/**
 * App core class for KIBLINE.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class KokusaiIBLine
{

  /**
   * Route the webhook to specific receivers.
   * 
   * @param Array $requests The array of Requests
   * @since 0.1
   */
  public function handle($requests) {

    foreach($requests as $request) {

      $receiver_name = $request->getReceiver();

      $class = 'KokusaiIBLine\\Receivers\\' . $receiver_name;
      $receiver = new $class($request->getEvent());
    
    }

  }

  /**
   * Check for new IB messages - called by cronjob.
   * 
   * @since 0.1
   */
  public function checkMessages() {

    $groups = ['All', 'JASL', 'EASL', 'HSL', 'CHL', 'PHL', 'MHL', 'TOK'];

    $managebac = new ManageBacAuthenticator();
    $managebac->authenticate();
    
    foreach($groups as $group) {

      // Construct, and use the session we created before.
      $message_getter = new ManageBacMessageGetter(
        $managebac->csrf_token,
        $managebac->session,
        $managebac->client
      );

      // Use the stored tokens to get the messages in the IB students group (grade-wide)
      $messages_data = $message_getter->{'get' . $group}();

      // Initiate a receiver, which checks if there are new messages, and sends the new ones
      $receiver = new ManageBacMessageReceiver($messages_data, $group);

    }

  }

}