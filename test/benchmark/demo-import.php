<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 19:03
 */
namespace Test;

use Phore\FileSystem\GzFileStream;
use Phore\Log\PhoreStopWatch;
use Talpa\BinFmt\V1\TDataWriter;

require __DIR__ . "/../../vendor/autoload.php";


$outFp = new GzFileStream("demo.bin", "w");
$writer = new TDataWriter($outFp, ["some"=>"metadata"]);


$fp = fopen("/opt/mock/pescher.tsv", "r");
if ( ! $fp)
    die ("nope");

$colIdMap = [];

while ( ! feof($fp)) {
    $data = fgetcsv($fp, 0, "\t");
    //echo ".";

    if (count($data) !== 4) {
        echo "\nignore" . print_r($data);
        continue;
    }


    $timestamp = $data[0];
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

    $writer->inject($timestamp, $colId, $value);
}

$writer->close();
print_r ($writer->getStats());
