<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 20:14
 */
namespace Test;

use Phore\Log\PhoreStopWatch;
use Talpa\BinFmt\V1\TDataReader;
use Talpa\BinFmt\V1\TDataWriter;

require __DIR__ . "/../../vendor/autoload.php";

$sw = new PhoreStopWatch();
$fp = fopen("demo.sec.bin", "r");

$index = 0;
$wrapper = new TDataReader($fp);
$wrapper->setOnDataCb(function($ts, $colId, $data) use(&$index) {
    $index++;
    //echo "\n$ts: $colId - $data";
});


$wrapper->parse();

echo $sw->printTime("time");
echo $index;
