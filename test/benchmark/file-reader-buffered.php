<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 22.08.18
 * Time: 11:04
 */
namespace Test;

use Phore\Log\PhoreStopWatch;

require __DIR__ . "/../../vendor/autoload.php";

define("FORMAT_READ", "Ca/vb");
$file = fopen ("/tmp/bench.dat", "w+");
echo "\nStart write...";
$i = 0;
$blockLen = 0;
for ($bufferIndex=0; $bufferIndex<200000; $bufferIndex++) {
    fwrite($file, $p = pack("f", microtime(true)));
    //echo "len: ". strlen ($p);
    if ($i++ == 0) {
        $blockLen = strlen($p);
        echo "\nLen is: " . $blockLen;
    }
}

fseek($file, 0);
echo "\nFilesize is: [kb] " . (int)( filesize("/tmp/bench.dat") / 1024 );


$sw = new PhoreStopWatch();
echo "\nReading blockwise.... ";
$sw->reset();
$buf = "";
$bufferIndex = 0;
while ( ! feof ($file)) {
    $buf = fread($file, $blockLen);
    if ($buf == "")
        break;
    $unpacked = unpack(FORMAT_READ, $buf);
    $bufferIndex++;
}
//print_r ($unpacked);
echo $sw->printTime("Reading time ($bufferIndex ds): ");


fseek($file, 0);

$sw = new PhoreStopWatch();
echo "\nReading sequential... ";
$sw->reset();
$buf = "";
$bufferIndex = 0;
while ( ! feof ($file)) {
    $buf .= fread($file, 1024);

    while (strlen($buf) >= $blockLen) {

        $data = substr($buf,0, $blockLen);
        $buf = substr($buf, $blockLen);
        $bufferIndex++;
        $unpacked = unpack(FORMAT_READ, $data);

    }
}
//print_r ($unpacked);
echo $sw->printTime("Reading time ($bufferIndex ds): ");


fseek($file, 0);
$sw = new PhoreStopWatch();
echo "\nReading buf-indexed.. ";
$sw->reset();
$buf = "";
$bufferIndex = 0;
while ( ! feof ($file)) {
    $buf .= fread($file, 1024);

    if ($buf === "")
        break;

    while (true) {

        $data = substr($buf,0, $blockLen);
        if (strlen($data) < $blockLen)
            break;
        $buf = substr($buf, $blockLen);
        $bufferIndex++;
        $unpacked = unpack(FORMAT_READ, $data);

    }
}
//print_r ($unpacked);
echo $sw->printTime("Reading time ($bufferIndex ds): ");

fseek($file, 0);
$sw = new PhoreStopWatch();
echo "\nReading indexed...... ";
$sw->reset();
$buf = "";
$bufferIndex = 0;
$i = 0;
$pointer = 0;
while ( ! feof ($file)) {
    $buf .= fread($file, $blockLen * 1000);

    if ($buf === "")
        break;

    $bufLen = strlen($buf);
    while (true) {
        if ($bufLen < ($bufferIndex+1) * $blockLen ) {
            $buf = substr($buf,$bufferIndex * $blockLen);
            $bufferIndex = 0;
            break;
        }
        $data = substr($buf,$bufferIndex * $blockLen, $blockLen);
        $bufferIndex++;
        $unpacked = unpack(FORMAT_READ, $data);
        $i++;
    }
}
//print_r ($unpacked);
echo $sw->printTime("Reading time ($i ds): ");

echo "\n";
