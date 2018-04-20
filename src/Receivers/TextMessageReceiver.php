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
   * Constructor and basic handler.
   * 
   * @param Array $event The array-parsed JSON payload of the event.
   * {"events":[{"type":"message","replyToken":"3514635493e34ec084810ac1c4232070","source":{"userId":"U5be75f0130106ee42c8fb194c302f7b9","type":"user"},"timestamp":1519029202891,"message":{"type":"text","id":"7499874981201","text":"Hhg"}}]}
   * @since 0.1
   */
  public function __construct($event) {

    $text = $event['message']['text'];

    $reply = [
      'replyToken' => $event['replyToken'],
      'messages' => []
    ];

    if(strpos(strtolower($text), 'when is the next biology class') !== false ||
       strpos(strtolower($text), 'when is the next bio class') !== false) {
      $now = new DateTime();
      $now->setTimezone(new DateTimeZone('Asia/Tokyo'));

      $biology = [
        [
          'start' => 'Wednesday 13:50',
          'end' => 'Wednesday 14:35'
        ],[
          'start' => 'Wednesday 14:45',
          'end' => 'Wednesday 15:30'
        ]
      ];

      $biology1Start = DateTime::createFromFormat('l H:i', $biology[0]['start'], new DateTimeZone('Asia/Tokyo'));
      $biology1End = DateTime::createFromFormat('l H:i', $biology[0]['end'], new DateTimeZone('Asia/Tokyo'));
      $biology2Start = DateTime::createFromFormat('l H:i', $biology[1]['start'], new DateTimeZone('Asia/Tokyo'));
      $biology2End = DateTime::createFromFormat('l H:i', $biology[1]['end'], new DateTimeZone('Asia/Tokyo'));

      if($now > $biology2End || $now < $biology1Start) {
        // Is not in Biology right now
        $interval = $now->diff($biology1Start);
        if($interval->invert === 1) {
          $biology1Start->add(new DateInterval('P7D'));
          $biology1End->add(new DateInterval('P7D'));
          $biology2Start->add(new DateInterval('P7D'));
          $biology2End->add(new DateInterval('P7D'));
          $interval = $now->diff($biology1Start);
        }

        $remaining_time = [];
        // Check and display dates only if > 0
        if($interval->days !== 0) {
          array_push($remaining_time, (string)$interval->days . ' day' . (
            $interval->days > 1 ? 's' : ''
          ));
        }
        // Check and display hours only if > 0        
        if($interval->h !== 0) {
          array_push($remaining_time, (string)$interval->h . ' hour' . (
            $interval->h > 1 ? 's' : ''
          ));
        }
        // Check and display minutes only if days and hours === 0
        if($interval->days === 0 && $interval->h === 0) {
          array_push($remaining_time, (string)$interval->i . ' minute' . (
            $interval->i > 1 ? 's' : ''
          ));
        }
        array_push($reply['messages'], [
          'type' => 'text',
          'text' => 'Biology is in ' . implode(', ', $remaining_time)
        ]);
      } else if($now >= $biology1Start && $now <= $biology1End) {
        // First lesson of biology
        array_push($reply['messages'], [
          'type' => 'text',
          'text' => 'The first lesson of biology is ongoing. Pay attention!'
        ]);
      } else if($now >= $biology2Start && $now <= $biology2End) {
        // Second lesson of biology
        array_push($reply['messages'], [
          'type' => 'text',
          'text' => 'The second lesson of biology is ongoing. Pay attention!'
        ]);
      } else if($now > $biology1End && $now < $biology2Start) {
        // Break time between biology lessons
        array_push($reply['messages'], [
          'type' => 'text',
          'text' => 'It\'s break time, enjoy it!'
        ]);
      }
    }
    
    if(!empty($reply['messages'])) {
      $request = new LINERequest();
      $request->prepare('POST', 'message/reply', $reply);
      $request->send();
    }

  }

}