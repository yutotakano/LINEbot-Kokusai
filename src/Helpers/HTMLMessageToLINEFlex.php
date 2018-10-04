<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 0.1
 */

namespace KokusaiIBLine\Helpers;

error_reporting(E_ALL);
ini_set('display_errors', 1);

use \Exception;
use \DOMDocument;
use Sunra\PhpSimple\HtmlDomParser;

/**
 * Converts HTML ManageBac Messages (where new lines are already <br>s) into suitable assoc. array format
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 */
class HTMLMessageToLINEFlex
{
  
  /**
   * Contains the mapping between subject IDs and their comprehensive names
   * 
   * @var Array
   */
  private static $subject_mapping = [
    'All' => [
      'name' => 'IB 29th',
      'id' => 'ib'
    ],
    'JASL' => [
      'name' => 'Japanese A SL',
      'id' => 10901407
    ],
    'EASL' => [
      'name' => 'English A SL',
      'id' => 10901409
    ],
    'HSL' => [
      'name' => 'History SL',
      'id' => 10901414
    ],
    'CHL' => [
      'name' => 'Chemistry HL',
      'id' => 10901417
    ],
    'PHL' => [
      'name' => 'Physics HL',
      'id' => 10901419
    ],
    'MHL' => [
      'name' => 'Mathematics HL',
      'id' => 10901421
    ],
    'TOK' => [
      'name' => 'ToK',
      'id' => 10901422
    ],
    'EE' => [
      'name' => 'Extended Essay',
      'id' => 'ee'
    ]
  ];

  /**
   * Takes all the parameters as strings and mashes them up into one array that is basically
   * the final result sent to LINE. 
   * 
   * The reason why strings are accepted individually instead of passing the entire array is to
   * ensure the helper function can be used easily from other places when required sometime later.
   * 
   * @param String $id The ManageBac message ID extracted from HTML
   * @param String $title The title of the message
   * @param String $body The message body, with paragraph breaks replaced by two <br>s.
   * @param String $author The author of the message
   * @param String $subject_initial The 3/4 letter initials for the subject, used for mapping
   * @return Array
   * @since 0.1
   */
  public static function convert($id, $title, $body, $author, $subject_initial) {

    $altText_str = self::generateAltText($id, $subject_initial, $title, $body);

    $subject_arr = self::convertSubject($subject_initial);

    $title_arr = self::convertTitle($title);

    $author_arr = self::convertAuthor($author);

    [$body, $extracted_links_arr] = self::extractLinks($body);

    $body_arr = self::convertBody($body);

    $links_arr = self::convertLinks($extracted_links_arr);

    $button_arr = self::createButton($subject_initial, $id);
    
    $id_arr = self::convertID($id);

    $final_arr = self::createFinalArray($altText_str, $subject_arr, $title_arr, $author_arr, $body_arr, $links_arr, $button_arr, $id_arr);

    return $final_arr;

  }

  /**
   * Generate the alt text for old devices and PC. Also shown in the main chat menu.
   * 
   * @param String $subject_initial 3 or 4 letter acronym for the subject
   * @param String $title Title of the message
   * @param String $body HTML Body of the message
   * @return String
   * @since 0.1
   */
  private static function generateAltText($id, $subject_initial, $title, $body) {

    $subject = self::$subject_mapping[$subject_initial];
    if($subject['id'] === 'ib') {
      $uri = 'https://kokusaiib.managebac.com/student/ib/messages/' . $id; 
    } else if($subject['id'] === 'ee') {
      $uri = 'https://kokusaiib.managebac.com/student/groups/10902178/messages/' . $id;
    } else {
      $uri = 'https://kokusaiib.managebac.com/student/classes/' . $subject['id'] . '/messages/' . $id;
    }

    // Alt text has a max of 400
    return '▨' . substr($subject['name'] . ': ' . htmlspecialchars_decode($title, ENT_QUOTES) . "\n\nIf you are running an old LINE version or the PC version and can only see this text, please use the link instead: " . $uri, 0, 390);

  }

  /**
   * Convert the subject initial into its own array. 
   * 
   * @param String $subject_initial 3 or 4 letter acronym for the subject
   * @return Array
   * @since 0.1
   */
  private static function convertSubject($subject_initial) {

    return [
      'type' => 'text',
      'text' => 'NEW MESSAGE FOR ' . strtoupper(self::$subject_mapping[$subject_initial]['name']),
      'color' => '#1DB446',
      'size' => 'xxs'
    ];

  }

  /**
   * Convert the title into its own array. Simple.
   * 
   * @param String $title Title of the message
   * @return Array
   * @since 0.1
   */
  private static function convertTitle($title) {

    return [
      'type' => 'text',
      'text' => htmlspecialchars_decode($title, ENT_QUOTES),
      'weight' => 'bold',
      'size' => 'md',
      'margin' => 'md',
      'wrap' => true
    ];

  }

  /**
   * Convert the author into its own array. Simple.
   *
   * @param String $author Author of the message
   * @return Array
   * @since 0.1
   */
  private static function convertAuthor($author) {

    return [
      'type' => 'text',
      'text' => $author,
      'size' => 'xs',
      'margin' => 'sm',
      'color' => '#aaaaaa',
      'wrap' => true
    ];

  }

  /**
   * Extract the links from the body, keep it in a different array, and also add citations in the body
   * replacing the links with non-tag ones
   * 
   * @param String $body Body HTML
   * @return Array[String] The body HTML with no more <a>links</a>
   * @return Array[Array] The array of links found within the body
   * @since 0.1
   */
  private static function extractLinks($body) {

    $dom = HtmlDomParser::str_get_html($body);

    $links = [];

    $counter = 1;

    // For each link, replace the text in the body and add the link to the links array
    foreach($dom->find('a') as $link) {

      // Remove from tree, replace with citation
      if(substr($link->href, 0, 7) !== 'mailto:') {
        // Add link to the array
        $links[] = $link->href;
        $link->outertext = $link->innertext . ' [' . $counter . ']';
        $counter++;
      } else {
        $link->outertext = $link->innertext;
      }

    }

    return [$dom->save(), $links];

  }

  /**
   * Convert the body HTML (links removed, newlines as <br>) to final array format.
   * 
   * @param String $body Body HTML
   * @return Array
   * @since 0.1
   */
  private static function convertBody($body) {

    $body_paragraphs = preg_split('/(<br>|<br\/>|<br \/>)/', $body);

    // Final array that will be returned
    $body_paragraphs_arr = [];

    foreach($body_paragraphs as $key => $paragraph) {

      if($paragraph === '') continue;
      $paragraph_arr = [
        'type' => 'text',
        'text' => $paragraph,
        'wrap' => true,
        'size' => 'xs',
        'margin' => 'none'
      ];
      
      // Add margin if the previous paragraph was empty. First check if it's not first paragraph.
      if($key > 0) {
        // Then check if previous paragraph was empty
        if($body_paragraphs[$key - 1] === '') {
          $paragraph_arr['margin'] = 'lg';
        }
      }

      $body_paragraphs_arr[] = $paragraph_arr;

    }

    return $body_paragraphs_arr;

  }

  /**
   * Convert the list of links found in the body to final array format.
   * 
   * @param Array $extracted_links_arr Array of extracted links from the body
   * @return Array
   * @since 0.1
   */
  private static function convertLinks($extracted_links_arr) {

    // Final array that will be returned
    $links_arr = [];

    foreach($extracted_links_arr as $key => $link) {

      $link_arr = [
        'type' => 'text',
        'text' => '[' . ($key + 1) . '] ' . $link,
        'wrap' => true,
        'size' => 'xxs',
        'margin' => 'none',
        'color' => '#aaaaaa',
        'action' => [
          'type' => 'uri',
          'label' => 'Link number ' . ($key + 1),
          'uri' => $link
        ]
      ];

      if($key === 0) $link_arr['margin'] = 'xl';

      $links_arr[] = $link_arr;

    }

    return $links_arr;

  }

  /**
   * Create the "Open in ManageBac" button.
   * 
   * @param String $id ManageBac ID of the message
   * @return Array
   * @since 0.1
   */
  private static function createButton($subject_initial, $id) {

    $subject = self::$subject_mapping[$subject_initial];
    if($subject['id'] === 'ib') {
      $uri = 'https://kokusaiib.managebac.com/student/ib/messages/' . $id; 
    } else if($subject['id'] === 'ee') {
      $uri = 'https://kokusaiib.managebac.com/student/groups/10902178/messages/' . $id;
    } else {
      $uri = 'https://kokusaiib.managebac.com/student/classes/' . $subject['id'] . '/messages/' . $id;
    }

    return [
      'type' => 'button',
      'action' => [
        'type' => 'uri',
        'label' => 'Open in ManageBac',
        'uri' => $uri
      ],
      'style' => 'link',
      'color' => '#6488da',
      'margin' => 'lg',
      'height' => 'sm'
    ];

  }

  /**
   * Convert ID to its own array. Dead simple.
   * 
   * @param String $id ManageBac ID of the message
   * @return Array
   * @since 0.1
   */
  private static function convertID($id) {

    return [
      'type' => 'box',
      'layout' => 'horizontal',
      'margin' => 'md',
      'contents' => [
        [
          'type' => 'text',
          'text' => 'ManageBac ID',
          'size' => 'xs',
          'color' => '#aaaaaa',
          'flex' => 0
        ], [
          'type' => 'text',
          'text' => '#' . $id,
          'color' => '#aaaaaa',
          'size' => 'xs',
          'align' => 'end'
        ]
      ]
    ];

  }

  private static function createFinalArray(
    $altText_str, $subject_arr, $title_arr, $author_arr, $body_arr, $links_arr, $button_arr, $id_arr) {

      return [
        'type' => 'flex',
        'altText' => $altText_str,
        'contents' => [
          'type' => 'bubble',
          'styles' => [
            'footer' => [
              'separator' => true
            ]
          ],
          'body' => [
            'type' => 'box',
            'layout' => 'vertical',
            'contents' => [
              $subject_arr,
              $title_arr,
              $author_arr,
              [
                'type' => 'separator',
                'margin' => 'xxl'
              ],
              [
                'type' => 'box',
                'layout' => 'vertical',
                'margin' => 'xxl',
                'contents' => array_merge($body_arr, $links_arr)
              ],
              $button_arr,
              [
                'type' => 'separator',
                'margin' => 'xxl'
              ],
              $id_arr
            ]
          ] 
        ]
      ];

  }

}