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
use KokusaiIBLine\Helpers\TextMessage;
use KokusaiIBLine\Helpers\TemplateMessage;
use DateTime;
use DateInterval;
use DateTimeZone;

/**
 * Receiver for text message webhooks
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class TextMessageReceiver
{

  /**
   * Contains an array of Columns for the Carousel Template message
   * 
   * @var Array
   */
  private $classes = [
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_All.png',
      'title' => 'Kokusai Class of 2020 ("All")',
      'text' => 'Main Group',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"all"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_JASL.png',
      'title' => 'Japanese A: Lit SL ("JASL")',
      'text' => 'Mr. Kaihotsu',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"jasl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_EASL.png',
      'title' => 'English A: LangLit SL ("EASL")',
      'text' => 'Mr. Greathead',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"easl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_HSL.png',
      'title' => 'History SL ("HSL")',
      'text' => 'Mr. Brown, Mr. Aoki, Ms. Jin',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"hsl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_CHL.png',
      'title' => 'Chemistry HL ("CHL")',
      'text' => 'Ms. Kirkpatrick',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"chl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_PHL.png',
      'title' => 'Physics HL ("PHL")',
      'text' => 'Ms. Quema',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"phl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_MHL.png',
      'title' => 'Mathematics HL ("MHL")',
      'text' => 'Ms. Kohsai, Mr. Dean, Ms. Tamura, Mr, Boodram',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"mhl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_TOK.png',
      'title' => 'Theory of Knowledge ("TOK")',
      'text' => 'Mr. Dean, Mr. Nomura, Mr. Abbenes, Mr. Boodram',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"mhl"}}'
        ]
      ]
    ],
    [
      'thumbnailImageUrl' => APP_ROOT . '/assets/SubjectThumbnail_EE.png',
      'title' => 'Extended Essay ("EE")',
      'text' => 'Ms. Vaughns',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"ee"}}'
        ]
      ]
    ]
  ];

  /**
   * Constructor and basic handler.
   * 
   * @param Array $event The array-parsed JSON payload of the event.
   * {"type":"message","replyToken":"3514635493e34ec084810ac1c4232070","source":{"userId":"U5be75f0130106ee42c8fb194c302f7b9","type":"user"},"timestamp":1519029202891,"message":{"type":"text","id":"7499874981201","text":"Hhg"}}
   * @since 0.1
   */
  public function __construct($event) {

    $text = trim($event['message']['text']);

    $reply = [
      'replyToken' => $event['replyToken'],
      'messages' => []
    ];

    if(strtolower(substr($text, 0, 17)) === 'unsubscribe from ') {
      
      $messages = $this->unsubscribeMessageHandler($event);
      foreach($messages as $message) {
        array_push($reply['messages'], $message->data);
      }

    }

    if(strtolower(substr($text, 0, 13)) === 'subscribe to ') {

      $messages = $this->subscribeMessageHandler($event);
      foreach($message as $message) {
        array_push($reply['messages'], $message->data);
      }

    }

    if(strtolower($text) === 'send help') {
      $message = new TextMessage('Here is what you can do:
◍ "subscribe to messages" (shows a list of groups you can subscribe to)
◍ "subscribe to <groupNameHere> messages" (subscribes you to that group)
◍ "unsubscribe from <groupNameHere> messages" (unsubscribes you from that group)
◍ "send help" (sends this)');
      array_push($reply['messages'], $message->data);
    }
    
    if(!empty($reply['messages'])) {
      $request = new LINERequest();
      $request->prepare('POST', 'message/reply', $reply);
      $request->send();
    }

  }

  /**
   * Handle unsubscribe request messages by checking everything, then calling removeRecipient()
   * 
   * @param Array $event The array-parsed JSON payload of the event.
   * @return Array
   * @since 1.0.1
   */
  private function unsubscribeMessageHandler($event) {

    try {
      $messageGroup = $this->detectMessageGroup(substr($text, 17, -9));
    } catch (Exception $e) {
      $message = new TextMessage('There is no group by the name ' . strtolower(substr($text, 17, -9)));
      return [$message];
    }

    try {
      list($id, $chatType) = $this->detectChatType($event);
    } catch (Exception $e) {
      $message = new TextMessage('Internal Error: Invalid Chat Type');
      return [$message];
    }

    // Remove recipient. Returns true on success, false or error string on failure.
    $status = $this->removeRecipient($id, $chatType, $messageGroup);

    if($status === true) {
      $message = new TextMessage(($chatType === 'user' ? 'You' : 'This ' . $chatType) . ' is no longer receiving messages from the "' . $messageGroup . '" group.');
    } else {
      echo $status;
      $message = new TextMessage(($status ?: 'An unknown error occurred.'));
    }
    return [$message];

  }

  /**
   * Handle subscribe request messages by checking everything, then calling subscribeHandler().
   * 
   * @param Array $event The array-parsed JSON payload of the event.
   * @return Array
   * @since 1.0.1
   */
  private function subscribeMessageHandler($event) {

    if(substr($text, 17, -9) === '') {
      $message1 = new TextMesasge('Here\'s a list of groups you can subscribe to:');
      $message2 = new TemplateMessage('carousel', $this->classes, 'Please view this on mobile:');
      return [$message1, $message2];
    } 

    try {
      $messageGroup = $this->detectMessageGroup(substr($text, 17, -9));
    } catch (Exception $e) {
      $message = new TextMessage('There is no group by the name ' . strtolower(substr($text, 17, -9)));
      return [$message];
    }

    try {
      list($id, $chatType) = $this->detectChatType($event);
    } catch (Exception $e) {
      $message = new TextMessage('Internal Error: Invalid Chat Type');
      return [$message];
    }

    // Remove recipient. Returns true on success, false or error string on failure.
    $status = $this->addRecipient($id, $chatType, $messageGroup);

    if($status === true) {
      $message = new TextMessage('Successfully subscribed ' . ($chatType === 'user' ? 'you' : 'this ' . $chatType) . ' to the "' . $messageGroup . '" message group.');
    } else {
      $message = new TextMessage(($status ?: 'An unknown error occurred.'));
    }
    return [$message];

  }

  /**
   * Detect class group name from the text.
   * @param String $text The part of the text that contains only group name
   * @return String
   * @since 1.0.1
   * 
   */
  private function detectMessageGroup($text) {

    switch($text) {
      case 'all':
      case 'jasl':
      case 'easl':
      case 'hsl':
      case 'chl':
      case 'phl':
      case 'mhl':
      case 'tok':
        return strtolower($text);
      default:
        throw new Exception('Invalid Group Name');
    }

  }

  /**
   * Detect chat type from the event.
   * @param Array $event JSON payload
   * @return String
   * @since 1.0.1
   */
  private function detectChatType($event) {

    if(isset($event['source']['roomId'])) {
      return [$event['source']['roomId'], 'room'];
    } else if(isset($event['source']['groupId'])) {
      return [$event['source']['groupId'], 'group'];
    } else if(isset($event['source']['userId'])) {
      return [$event['source']['userId'], 'user'];
    }
    throw new Exception('Invalid Chat Type');

  }
 

  /**
   * Add recipient to database.
   * 
   * @param String $id The LINE id of the room, group or user
   * @param String $chatType Either 'room', 'group', or 'user'
   * @param String $messageGroup Lowercase subject codes
   * @return Boolean|String
   * @since 0.1
   */
  private function addRecipient($id, $chatType, $messageGroup) {

    $conn = new \mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_DBNAME);
    if($conn->connect_error) {
      return $conn->connect_error;
    }

    $sql = "SELECT * FROM ib_recipients WHERE id=? AND chatType=? AND messageGroup=?";

    $stmt = $conn->prepare($sql);
    if(!$stmt) {
      return 'Error in database query';
    }
    $stmt->bind_param('sss', $id, $chatType, $messageGroup);

    $stmt->execute();

    // num_rows can only be executed after storing the results
    $stmt->store_result();

    // Store in a variable so we can close stmt
    $select_num_rows = $stmt->num_rows();

    // Close stmt and unset the variable for further cleaning
    $stmt->close();
    unset($stmt);

    if($select_num_rows !== 0) {
      // Row with same stuff exists.
      return 'You\'re already subscribed to this message group!';
    }

    $sql = "INSERT INTO ib_recipients (id, chatType, messageGroup) VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if(!$stmt) {
      return 'Error in database query';
    }
    $stmt->bind_param('sss', $id, $chatType, $messageGroup);

    $stmt->execute();

    if($stmt->affected_rows !== 1) {
      $error = $stmt->error;
      $stmt->close();
      return 'Database error: ' . $error;
    }
    
    $stmt->close();
    return true;

  }

  /**
   * Remove a recipient from the database.
   * 
   * @param String $id The LINE id of the room, group or user
   * @param String $chatType Either 'room', 'group', or 'user'
   * @param String $messageGroup Lowercase subject codes
   * @return Boolean|String
   * @since 0.1
   */
  private function removeRecipient($id, $chatType, $messageGroup) {
    
    $conn = new \mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_DBNAME);
    if($conn->connect_error) {
      return $conn->connect_error;
    }

    $sql = "DELETE FROM ib_recipients WHERE id=? AND chatType=? AND messageGroup=?";

    $stmt = $conn->prepare($sql);
    if(!$stmt) {
      return 'Error in database query';
    }
    $stmt->bind_param('sss', $id, $chatType, $messageGroup);

    $stmt->execute();

    // Store affected rows in a variable so we can close stmt
    $delete_affected_rows = $stmt->affected_rows;

    // Close stmt
    $stmt->close();

    if($delete_affected_rows === 0) {
      // Deleted no rows
      return 'You were never subscribed to this group!';
    } else if($delete_affected_rows > 1) {
      // Deleted multiple entries... damn
      return 'Critical database error: Multiple entries deleted';
    }

    return true;

  }

}