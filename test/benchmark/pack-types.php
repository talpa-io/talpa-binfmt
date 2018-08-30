<?php
/**
 *
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 17:18
 */


$in = 205;
$out = [
    (bool)($in & 0x01),
    (bool)($in & 0x02),
    (bool)($in & 0x40),

]






exit;
echo base_convert(59, 10, 2);
exit;


$input = 32448;

$data = chr((int)$input/256) . chr($input % 256);

echo ord($data[0]) * 256 + ord($data[1]);



exit;
print_r (
    unpack(
        "df",
        pack("d", microtime(true))
    )
);
