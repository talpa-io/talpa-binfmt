<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 16:50
 */

namespace Talpa\BinFmt\V1;


class TBinFmt
{
    const TYPE_NULL =  201;
    const TYPE_FALSE = 202;
    const TYPE_TRUE =  203;
    const TYPE_EMPTY_STRING = 204;

    const TYPE_INT8 =  210;
    const TYPE_INT16 = 211;
    const TYPE_INT32 = 212;
    const TYPE_INT64 = 213;

    const TYPE_INT8_NEG =  215;
    const TYPE_INT16_NEG = 216;
    const TYPE_INT32_NEG = 217;
    const TYPE_INT64_NEG = 218;

    const TYPE_DOUBLE = 220;
    const TYPE_FLOAT = 221;
    const TYPE_MIN3_FLOAT = 222; // 3 decimals
    const TYPE_MIN2_FLOAT = 223; // 2 decimals
    const TYPE_MIN1_FLOAT = 224; // 1 decimals

    const TYPE_MED_FLOAT = 225;

    const TYPE_MAP = [
        self::TYPE_INT8 =>      ["c", 1,  1],
        self::TYPE_INT8_NEG =>  ["c", 1, -1],

        self::TYPE_INT16 =>     ["S", 2,  1],
        self::TYPE_INT16_NEG => ["S", 2, -1],

        self::TYPE_INT32 =>     ["l", 4,  1],
        self::TYPE_INT32_NEG => ["l", 4, -1],

        self::TYPE_INT64 =>     ["q", 8,  1],
        self::TYPE_INT64_NEG => ["q", 8, -1],

        self::TYPE_MIN3_FLOAT => ["s", 2, 1000],
        self::TYPE_MIN2_FLOAT => ["s", 2, 100],
        self::TYPE_MIN1_FLOAT => ["s", 2, 10],

        self::TYPE_MED_FLOAT => ["l", 4, 10000], // 4 decimal places
        self::TYPE_DOUBLE =>    ["d", 8,  1],
        self::TYPE_FLOAT =>     ["f", 4,  1],
    ];

    const TYPE_STRING = 230;
    const TYPE_UNMODIFIED = 231;

    const TYPE_SET_TIMESTAMP = 240;
    const TYPE_SHIFT_TIMESTAMP = 241;


    const TYPE_FILE_VERSION = 250; // Talpa Combined Log Format
    const TYPE_NAME_ASSIGN = 251;
    const TYPE_EOF = 249;

    const TS_MULTIPLY = 1000;

    protected function int2binary(int $input)
    {
        return chr((int)($input / 255)) . chr($input % 255);
    }

    protected function bin2int (string $input) : int
    {
        return ord($input[0]) * 255 + ord($input[1]);
    }

}
