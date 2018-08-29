<?php
/**
 *
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 17:18
 */

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
