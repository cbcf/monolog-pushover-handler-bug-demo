<?php

require_once "./vendor/autoload.php";
require_once "PushoverHandlerWithFread.php";
require_once "PushoverHandlerWithKeepAlive.php";
require_once "PushoverHandlerWithFopen.php";
require_once "./util.php";
use Monolog\Handler\PushoverHandler;
use Monolog\Logger;


/* Configure me here */

// Dump responses to file for PushoverHandlerWithKeepAlive & PushoverHandlerWithFopen?
$debugDump = true;
// What level to send log at?
$level = Logger::NOTICE;
// How many messages to send (keep in mind, your monthly limit is 7500)
$count = 20;
// Which implementation variants to test?
$runTestsWith = [
    PushoverHandler::class                 => true,
    PushoverHandlerWithFread::class        => true,
    PushoverHandlerWithKeepAlive::class    => true,
    PushoverHandlerWithFopen::class        => true,
];

/* ----- */


global $PUSHOVER_API_APP_TOKEN;
global $PUSHOVER_API_USER_TOKEN;
include "./token.local.php";

echo "Bulk send test".PHP_EOL;
echo "==============".PHP_EOL.PHP_EOL;

// Ping pushover api to ensure even start for all classes
echo "Pinging api.pushover.net ...";
$a = microtime();
fclose(fsockopen('ssl://api.pushover.net:443'));
$b = microtime();
echo " took ".number_format(microTimeDiff($a, $b)*1000, 3)." ms.".PHP_EOL.PHP_EOL;

foreach($runTestsWith as $testClass => $doTest) {
    if ($doTest) {
        echo "Handler: ".$testClass.PHP_EOL;
    } else {
        echo "Skipping Handler: ".$testClass.PHP_EOL;
        continue;
    }

    $testId = substr(sha1(uniqid()), -8);
    echo "Test run ID: [" . $testId ."]".PHP_EOL;

    /** @var PushoverHandler $pushoverHandler */
    $pushoverHandler = new $testClass(
        $PUSHOVER_API_APP_TOKEN,
        $PUSHOVER_API_USER_TOKEN,
        'Bulk Send Test',
        Logger::DEBUG
    );

    $logger = new Logger('test');
    $logger->pushHandler($pushoverHandler);

    // Wait a bit so the messages in client will not intersect too much.
    // Also, makes results more comparable
    echo "Waiting 4 seconds ...".PHP_EOL;
    usleep(4*1000*1000);

    echo "Sending ".$count." messages ...".PHP_EOL;

    $times = [];

    for ($i = 1; $i <= $count; ++$i) {
        $message = sprintf("[%s : %02d]\nThis is example message number of the %s level.", $testId, $i, Logger::getLevelName($level));
        $a = microtime();
        $logger->log($level, $message);
        $b = microtime();

        $times[] = microTimeDiff($a, $b);
    }


    echo "Done." . PHP_EOL;

    echo "Time for first message: " . number_format(1000 * $times[0], 3) . " ms" . PHP_EOL;
    $followUpTimes = array_slice($times, 1);
    echo "Average time per followup messages: " . number_format(1000 * calcAverage($followUpTimes), 3) . " ms" . PHP_EOL;
    echo "Median time per followup messages: " . number_format(1000 * calcMedian($followUpTimes), 3) . " ms" . PHP_EOL;
    foreach ($times as $time) {
        echo "  - " . number_format($time * 1000, 3) . " ms" . PHP_EOL;
    }

    if ($debugDump && (
        $testClass == PushoverHandlerWithKeepAlive::class
        || $testClass == PushoverHandlerWithFopen::class
        )) {
        $fname = sprintf("%s.dump", $testId);
        echo "Dumping responses to ".$fname.PHP_EOL;
        $f = fopen($fname, "w");
        /** @var PushoverHandlerWithKeepAlive|PushoverHandlerWithFopen $pushoverHandler*/
        foreach($pushoverHandler->debugResponses as $response) {
            fwrite($f, "*** HEAD ***\r\n");
            fwrite($f, is_array($response['head']) ? join("\r\n", $response['head']) : $response['head']);
            fwrite($f, "\r\n*** BODY ***\r\n");
            fwrite($f, $response['body']);
            fwrite($f, "\r\n************\r\n\r\n");
        }
        fclose($f);
        echo "Done.".PHP_EOL;
    }
}
