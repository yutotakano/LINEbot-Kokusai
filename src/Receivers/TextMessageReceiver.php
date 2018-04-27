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
      'thumbnailImageUrl' => 'https://s3-ap-southeast-1.amazonaws.com/subscriber.images/chemistry/2017/04/21115856/bott.png',
      'title' => 'Chemistry HL',
      'text' => 'Ms. Kirkpatrick',
      'actions' => [
        [
          'type' => 'postback',
          'label' => 'Subscribe',
          'data' => '{"action":"subscribe_class","data":{"class_code":"chl"}}'
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

    if(strtolower(substr($text, 0, 13)) === 'subscribe to ') {

      switch(strtolower(substr($text, 13, -9))) {
        case 'all':
        case 'jasl':
        case 'easl':
        case 'hsl':
        case 'chl':
        case 'phl':
        case 'mhl':
        case 'tok':
          $messageGroup = strtolower(substr($text, 13, -9));
          break;
        case '':
          array_push($reply['messages'], [
            'type' => 'template',
            'altText' => 'Here\'s a list of message groups you can subscribe to... ',
            'template' => [
              'type' => 'carousel',
              'columns' => $this->classes
            ]
          ]);
          break;
      }

      if(isset($event['source']['roomId'])) {
        $id = $event['source']['roomId'];
        $chatType = 'room';
      } else if(isset($event['source']['groupId'])) {
        $id = $event['source']['groupId'];
        $chatType = 'group';
      } else if(isset($event['source']['userId'])) {
        $id = $event['source']['userId'];
        $chatType = 'user';
      }

      if(isset($messageGroup)) {
        // Add recipient. Returns true on success, false or error string on failure.
        $status = $this->addRecipient($id, $chatType, $messageGroup);

        print_r($status ? 'true' : 'false');

        if($status === true) {
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => 'Successfully subscribed you to the "' . $messageGroup . '" message group.'
          ]);
        } else {
          echo $status;
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => ($status ?: 'An unknown error occurred. Maybe you\'re already subscribed to this message group?')
          ]);
        }
      }

    }

    // if(strpos(strtolower($text), 'when is the next biology class') !== false ||
    //    strpos(strtolower($text), 'when is the next bio class') !== false) {
    //   $now = new DateTime();
    //   $now->setTimezone(new DateTimeZone('Asia/Tokyo'));

    //   $biology = [
    //     [
    //       'start' => 'Wednesday 13:50',
    //       'end' => 'Wednesday 14:35'
    //     ],[
    //       'start' => 'Wednesday 14:45',
    //       'end' => 'Wednesday 15:30'
    //     ]
    //   ];

    //   $biology1Start = DateTime::createFromFormat('l H:i', $biology[0]['start'], new DateTimeZone('Asia/Tokyo'));
    //   $biology1End = DateTime::createFromFormat('l H:i', $biology[0]['end'], new DateTimeZone('Asia/Tokyo'));
    //   $biology2Start = DateTime::createFromFormat('l H:i', $biology[1]['start'], new DateTimeZone('Asia/Tokyo'));
    //   $biology2End = DateTime::createFromFormat('l H:i', $biology[1]['end'], new DateTimeZone('Asia/Tokyo'));

    //   if($now > $biology2End || $now < $biology1Start) {
    //     // Is not in Biology right now
    //     $interval = $now->diff($biology1Start);
    //     if($interval->invert === 1) {
    //       $biology1Start->add(new DateInterval('P7D'));
    //       $biology1End->add(new DateInterval('P7D'));
    //       $biology2Start->add(new DateInterval('P7D'));
    //       $biology2End->add(new DateInterval('P7D'));
    //       $interval = $now->diff($biology1Start);
    //     }

    //     $remaining_time = [];
    //     // Check and display dates only if > 0
    //     if($interval->days !== 0) {
    //       array_push($remaining_time, (string)$interval->days . ' day' . (
    //         $interval->days > 1 ? 's' : ''
    //       ));
    //     }
    //     // Check and display hours only if > 0        
    //     if($interval->h !== 0) {
    //       array_push($remaining_time, (string)$interval->h . ' hour' . (
    //         $interval->h > 1 ? 's' : ''
    //       ));
    //     }
    //     // Check and display minutes only if days and hours === 0
    //     if($interval->days === 0 && $interval->h === 0) {
    //       array_push($remaining_time, (string)$interval->i . ' minute' . (
    //         $interval->i > 1 ? 's' : ''
    //       ));
    //     }
    //     array_push($reply['messages'], [
    //       'type' => 'text',
    //       'text' => 'Biology is in ' . implode(', ', $remaining_time)
    //     ]);
    //   } else if($now >= $biology1Start && $now <= $biology1End) {
    //     // First lesson of biology
    //     array_push($reply['messages'], [
    //       'type' => 'text',
    //       'text' => 'The first lesson of biology is ongoing. Pay attention!'
    //     ]);
    //   } else if($now >= $biology2Start && $now <= $biology2End) {
    //     // Second lesson of biology
    //     array_push($reply['messages'], [
    //       'type' => 'text',
    //       'text' => 'The second lesson of biology is ongoing. Pay attention!'
    //     ]);
    //   } else if($now > $biology1End && $now < $biology2Start) {
    //     // Break time between biology lessons
    //     array_push($reply['messages'], [
    //       'type' => 'text',
    //       'text' => 'It\'s break time, enjoy it!'
    //     ]);
    //   }
    // }
    
    if(!empty($reply['messages'])) {
      $request = new LINERequest();
      $request->prepare('POST', 'message/reply', $reply);
      $request->send();
    }

  }

  /**
   * Add recipient to database.
   * 
   * @return Boolean
   * @since 0.1
   */
  public function addRecipient($id, $chatType, $messageGroup) {

    $conn = new \mysqli(DB_SERVERNAME, DB_USERNAME, DB_PASSWORD, DB_DBNAME);
    if($conn->connect_error) {
      echo $conn->connect_error;
      return false;
    }

    $sql = "SELECT * FROM ib_recipients WHERE id=? AND chatType=? AND messageGroup=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $id, $chatType, $messageGroup);

    $stmt->execute();

    $stmt->store_result();

    if(!$stmt || $stmt->num_rows !== 0) {
      // Row with same stuff exists.
      $stmt->close();
      return 'You\'re already subscribed to this message group!';
    }

    $stmt->close();
    unset($stmt);

    $sql = "INSERT INTO ib_recipients (id, chatType, messageGroup) VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $id, $chatType, $messageGroup);

    $stmt->execute();

    if(!$stmt || $stmt->affected_rows !== 1) {
      $error = $stmt->error;
      $stmt->close();
      return 'Database error: ' . $error;
    }
    
    $stmt->close();
    return true;

  }

}