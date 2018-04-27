<?php

/**
 * A part of the Kokusai IB 29th LINE Bot Webhook Receiver.
 * 
 * @since 0.1
 * @author Yuto Takano <moa17stock@gmail.com>
 * @version 0.1
 */

require __DIR__ . '/../vendor/autoload.php';

use KokusaiIBLine\Builders\RequestBuilder;
use KokusaiIBLine\KokusaiIBLine;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start core.
$KIBLINE = new KokusaiIBLine();
$KIBLINE->checkMessages();

http_response_code(200);