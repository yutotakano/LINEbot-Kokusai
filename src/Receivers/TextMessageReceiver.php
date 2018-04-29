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
      'text' => 'Mr. Greatjhead',
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

    print_r($this->classes);

    $text = trim($event['message']['text']);

    $reply = [
      'replyToken' => $event['replyToken'],
      'messages' => []
    ];

    if(strtolower(substr($text, 0, 17)) === 'unsubscribe from ') {
      
      switch(strtolower(substr($text, 17, -9))) {
        case 'all':
        case 'jasl':
        case 'easl':
        case 'hsl':
        case 'chl':
        case 'phl':
        case 'mhl':
        case 'tok':
          $messageGroup = strtolower(substr($text, 17, -9));
          break;
        default:
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => 'There is no group by the name ' . strtolower(substr($text, 17, -9))
          ]);
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

        // Remove recipient. Returns true on success, false or error string on failure.
        $status = $this->removeRecipient($id, $chatType, $messageGroup);

        if($status === true) {
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => ($chatType === 'user' ? 'You' : 'This ' . $chatType) . ' is no longer receiving messages from the "' . $messageGroup . '" group.'
          ]);
        } else {
          echo $status;
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => ($status ?: 'An unknown error occurred.')
          ]);
        }
      }

    }

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
            'type' => 'text',
            'text' => 'Here\'s a list of message groups you can subscribe to:'
          ], [
            'type' => 'template',
            'altText' => 'Please view this on mobile: ',
            'template' => [
              'type' => 'carousel',
              'columns' => $this->classes
            ]
          ]);
          break;
        default:
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => 'There is no group by the name ' . strtolower(substr($text, 13, -9))
          ]);
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

        if($status === true) {
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => 'Successfully subscribed ' . ($chatType === 'user' ? 'you' : 'this ' . $chatType) . ' to the "' . $messageGroup . '" message group.'
          ]);
        } else {
          echo $status;
          array_push($reply['messages'], [
            'type' => 'text',
            'text' => ($status ?: 'An unknown error occurred.')
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