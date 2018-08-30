<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 19:03
 */
namespace Test;

use Phore\CloudStore\Driver\GoogleCloudStoreDriver;
use Phore\CloudStore\ObjectStore;
use Phore\FileSystem\FileStream;
use Phore\FileSystem\GzFileStream;
use Phore\Log\Logger\PhoreEchoLogger;
use Phore\Log\PhoreLog;
use Phore\Log\PhoreStopWatch;
use Talpa\BinFmt\V1\TDataWriter;
use Talpa\BinFmt\V1\TMachineWriter;

require __DIR__ . "/../../vendor/autoload.php";

PhoreLog::Init(new PhoreEchoLogger());
PhoreLog::GetInstance()->setVerbosity(9);


$mw = new TMachineWriter("TST_TEST_001", new ObjectStore(new GoogleCloudStoreDriver(__DIR__ . "/../../etc/talpa-backend-a938dc597171.json", TMachineWriter::BUCKET)));
$mux = $mw->openInterval(134);

$fp = new FileStream("/opt/mock/pescher.tsv", "r");

while ( ! $fp->feof()) {
    $data = $fp->freadcsv(0, "\t");

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

    //phore_log("Ts: {$timestamp} ColName: $colName Measure: {$mu} Value: {$value}");
    $mux->injectValue($timestamp, $colName, $mu, $value);
}

$mux->close();
