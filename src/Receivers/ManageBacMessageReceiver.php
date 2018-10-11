<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

namespace KokusaiIBLine\Receivers;

use KokusaiIBLine\Helpers\LINERequest;
use KokusaiIBLine\Helpers\HTMLMessageToLINEFlex;
use \Exception;

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
   * @param String $filename The cache filename for the messages.
   * @since 0.1
   */
  public function __construct($current_messages, $subject = 'All') {

    // Create cache folder if not exist
    if(!file_exists(__DIR__ . '/../../app/cache')) {
      mkdir(__DIR__ . '/../../app/cache', 0777, true);
    }

    if(!file_exists(__DIR__ . '/../../app/cache/IB' . $subject . 'Messages.json')) {
      
      $previous_messages = [];
    
    } else { 

      $previous_messages = @json_decode(
        file_get_contents(__DIR__ . '/../../app/cache/IB' . $subject . 'Messages.json')
      , true) ?? [];

    }

    file_put_contents(__DIR__ . '/../../app/cache/IB' . $subject . 'Messages.json', json_encode($current_messages));

    // Use a custom diff function (udiff) instead of array_diff
    // because array_diff does not work on multidimensional arrays
    // https://stackoverflow.com/questions/11821680/array-diff-with-multidimensional-arrays
    $new_messages = array_udiff($current_messages, $previous_messages, function($a, $b) {
      // Spaceship operator (returns -1 if right is greater, 1 if left is greater, 0 if equal)
      return $a['id'] <=> $b['id'];
    });

    $new_messages = array_reverse($new_messages); // So that posting them becomes chronological

    // Get list of recipients to filter before sending the messages
    $recipients = $this->getRecipients($subject);

    $users = array_filter($recipients, function($item) {
      return ($item['chatType'] == 'user');
    });

    $groups = array_filter($recipients, function($item) {
      return ($item['chatType'] == 'group');
    });
    
    $rooms = array_filter($recipients, function($item) {
      return ($item['chatType'] == 'room');
    });

    // Loop for every message before every recipient
    foreach($new_messages as $new_message) {

      $message_data = HTMLMessageToLINEFlex::convert(
        $new_message['id'],
        $new_message['title'],
        $new_message['body'],
        $new_message['author'],
        $subject
      );

      // Since sending to multiple users is different from sending to groups/rooms
      // Send to users first, using multicast. Simultaneously sends.
      // if(!empty($users)) {
        
      //   $request = new LINERequest();
      //   $request->prepare('POST', 'message/multicast', [
      //     'to' => array_column($users, 'id'),
      //     'messages' => [
      //       $message_data
      //     ]
      //   ]);
      //   $request->send();

      // }

      // Next, send to each group or room one by one.
      foreach(array_merge($users, $groups, $rooms) as $recipient) {

        $request = new LINERequest();
        $request->prepare('POST', 'message/push', [
          'to' => $recipient['id'],
          'messages' => [
            $message_data
          ]
        ]);
        
        $request->send();

      }
    }

  }

  /**
   * Get recipients from the database.
   * 
   * @return Array
   * @since 0.1
   */
  public function getRecipients($subject = 'All') {

    // Lowercase them
    $database_subjectname = strtolower($subject);

    $conn = new \mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_DBNAME);
    if($conn->connect_error) {
      return false;
    }

    $sql = "SELECT id, chatType, messageGroup FROM ib_recipients WHERE messageGroup = '{$database_subjectname}'";

    $result = $conn->query($sql);

    $array = [];

    if($result->num_rows > 0) {
      while($row = $result->fetch_assoc()) {
        array_push($array, $row);
      }
    }

    echo 'getRecipients():';
    print_r($array);
    return $array;
    

  }

}