<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../tex2png/vendor/autoload.php';

use KokusaiIBLine\Builders\RequestBuilder;
use KokusaiIBLine\KokusaiIBLine;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start recording output
ob_start();

// Build instance of Request.
$request_builder = new RequestBuilder();
$request_builder->build();
$requests = $request_builder->getRequests();

if($requests) {
  // Start core.
  $KIBLINE = new KokusaiIBLine();
  $KIBLINE->handle($requests);
}

$log = ob_get_clean();

// Don't write in log if there was no output
if(!empty(trim($log))) {
  $log .= PHP_EOL . "----------------------------" . PHP_EOL . PHP_EOL;
  $logFile = "logs/log_" . date("Y.m.d") . ".txt";
  file_put_contents($logFile, $log, FILE_APPEND);
  chmod($logFile, 0777);
}

http_response_code(200);