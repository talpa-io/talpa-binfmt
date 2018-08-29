<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 14:40
 */

namespace Talpa;

use MessagePack\BufferUnpacker;
use MessagePack\MessagePack;
use MessagePack\Packer;
use MessagePack\Type\Map;
use Talpa\BinFmt\TalpaDataFormat;

require __DIR__ . "/../vendor/autoload.php";

$wurst = [];
$packer = new Packer();
for ($i=0; $i<1000000; $i++) {
    $wurst[] = [
        55.2849,//microtime(true),
        4,
        false
    ];

}



echo "seeding,";
$data = gzencode(MessagePack::pack($wurst), 5);
echo strlen($data);
/*

$fmt = new TalpaDataFormat();

$data = $fmt->pack($t = microtime(true), 230, 123456);
$data .= "\n" . $fmt->pack($t = microtime(true), 2440, 123);


echo "$t -> $data (len: " . strlen($data) . ")";


print_r ($fmt->unpack($data));

foreach (explode("\n", $data) as $cur)
    print_r ($fmt->unpack($cur));


*/
