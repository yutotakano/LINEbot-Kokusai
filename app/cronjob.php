<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 1.0.1
 */

require __DIR__ . '/../vendor/autoload.php';

use KokusaiIBLine\Builders\RequestBuilder;
use KokusaiIBLine\KokusaiIBLine;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

error_reporting(E_ALL);
ini_set('display_errors', 1);

ob_start();

// Start core.
$KIBLINE = new KokusaiIBLine();
$KIBLINE->checkMessages();

$log = ob_get_clean();

// Don't write in log if there was no output
if (!empty(trim($log))) {
  $log .= PHP_EOL . "----------------------------" . PHP_EOL . PHP_EOL;
  $logFile = "logs/log_" . date("Y.m.d") . ".txt";
  file_put_contents($logFile, $log, FILE_APPEND);
  chmod($logFile, 0777);
}

http_response_code(200);