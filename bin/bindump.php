#!/bin/bash
<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 14:34
 */

require __DIR__ . "/../vendor/autoload.php";

$stream = new \Talpa\BinFmt\TalpaDataFormat();

$input = fopen($argv[1], "r");

while( ! feof($input)) {
    $line = fgets($input);
    print_r ($stream->unpack($line));
    echo "\n";
}
