<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 21:50
 */
namespace Test;

use Phore\FileSystem\GzFileStream;
use Phore\Log\PhoreStopWatch;
use Talpa\BinFmt\V1\TDataWriter;

require __DIR__ . "/../../vendor/autoload.php";


$outFp = new GzFileStream("demo.3.bin", "w");
$writer = new TDataWriter($outFp, ["some"=>"metadata"]);


$fp = fopen("/opt/mock/pescher.tsv", "r");
if ( ! $fp)
    die ("nope");

$colIdMap = [];
$oldTs = 0;

$secData = [];
while ( ! feof($fp)) {
    $data = fgetcsv($fp, 0, "\t");
    //echo ".";

    if (count($data) !== 4) {
        echo "\nignore" . print_r($data);
        continue;
    }


    $timestamp = ((int)($data[0] / 3)) * 3;
    $colName = $data[1];
    $mu = $data[2];
    $value = $data[3];
    if ($mu == "bit" && $value == 3)
        continue;
    if ($value == "")
        continue;
    if (!isset($colIdMap[$colName]))
        $colIdMap[$colName] = count($colIdMap);
    $colId = $colIdMap[$colName];

    if ($timestamp === $oldTs) {
        $secData[$colId] = $value;
    } else {
        if ($oldTs !== null) {
            echo $timestamp . "\n";
            foreach ($secData as $key => $value)
                $writer->inject($timestamp, $key, $value);
        }
        $secData = [];
        $oldTs = $timestamp;
    }



}

$writer->close();
print_r ($writer->getStats());

