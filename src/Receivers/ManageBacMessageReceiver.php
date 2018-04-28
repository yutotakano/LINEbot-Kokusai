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
   * Conatins the mapping between subject IDs and their comprehensive names
   * 
   * @var Array
   */
  private $subject_mapping = [
    'All' => 'IB 29th',
    'JASL' => 'Japanese A SL',
    'EASL' => 'English A SL',
    'HSL' => 'History SL',
    'CHL' => 'Chemistry HL',
    'PHL' => 'Physics HL',
    'MHL' => 'Mathematics HL',
    'TOK' => 'ToK'
  ];

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

      // Cut down and add ellipsis for message over 200 characters
      $new_message['body'] = (strlen($new_message['body']) > 1800) ? substr($new_message['body'], 0, 1800) . '...' : $new_message['body']; 
      
      $newline_replacers = ['<br />', '<br>', '<br/>'];
      $title = $this->subject_mapping[$subject];
      $message_data = [
        [
          'type' => 'text',
          'text' => '▨' . $title . ': ' . $new_message['title'] . "\n\n" . str_ireplace($newline_replacers, "\n", $new_message['body'])
        ]
      ];

      if(!empty($users)) {
        
        $request = new LINERequest();
        $request->prepare('POST', 'message/multicast', [
          'to' => array_column($users, 'id'),
          'messages' => $message_data
        ]);
        $request->send();

      }

      foreach(array_merge($groups, $rooms) as $recipientNotUser) {

        $request = new LINERequest();
        $request->prepare('POST', 'message/push', [
          'to' => $recipientNotUser['id'],
          'messages' => $message_data
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

    return $array;
    

  }

}