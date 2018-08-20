<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 15:15
 */

namespace Test;

use Talpa\BinFmt\BinStreamReader;
use Talpa\BinFmt\BinStreamWriter;
use Talpa\BinFmt\ColumnIndex;

require __DIR__ . "/../vendor/autoload.php";



$writer = new BinStreamWriter($colIndex = new ColumnIndex([]));

echo "\nStart write...";
for ($i=0; $i<10000000; $i++)
    $writer->write(microtime(true), "abc", "t/s", mt_rand(0,65555));

echo "OK";

echo "\nStart read...";
$reader = new BinStreamReader($colIndex);
$data = $writer->getData();

echo "Len: " . strlen($data);

$reader->setData($data);
echo "parsing";
$reader->each(function ($ts, $colId, $value) {
    //echo $colId . $value;
});
echo "END";

