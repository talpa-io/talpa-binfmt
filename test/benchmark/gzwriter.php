<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 22.08.18
 * Time: 13:10
 */
namespace Test;

use Phore\Log\PhoreStopWatch;

require __DIR__ . "/../../vendor/autoload.php";

define("FORMAT_READ", "Va/vb/Cc");
$file = gzopen("/tmp/bench.dat", "w");
echo "\nStart write...";
$i = 0;
$blockLen = 0;

$sw = new PhoreStopWatch();

for ($bufferIndex=0; $bufferIndex<10000000; $bufferIndex++) {
    gzwrite($file, $p = pack("VvC", microtime(true) * 100, 2, 1));
    //echo "len: ". strlen ($p);
    if ($i++ == 0) {
        $blockLen = strlen($p);
        echo "\nLen is: " . $blockLen;
    }
}

$sw->printTime("time");


