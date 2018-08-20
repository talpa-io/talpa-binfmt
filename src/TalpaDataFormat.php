<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 17.08.18
 * Time: 19:43
 */

namespace Talpa\BinFmt;


class TalpaDataFormat
{

    const VERSION = 1;

    const TYPE_NULL = 0;
    const TYPE_INT = 1;
    const TYPE_FLOAT = 2;
    const TYPE_STRING = 9;



    public function pack(float $ts, int $colId, $value)
    {
        $strVal = "";
        $floatVal = 0;
        if (is_null($value) || $value === "") {
            $valueType = self::TYPE_NULL;
        } elseif (is_numeric($value)) {
            $valueType = self::TYPE_FLOAT;
            $floatVal = $value;
        } else {
            $valueType = self::TYPE_STRING;
            $strVal = $value;
        }
        return pack("gvvga*", $ts, $colId, $valueType, $floatVal, $strVal);
    }

    public function unpack (string $binary)
    {
        $data = unpack("gts/vcolId/vtype/gfloatVal/a*strVal", $binary);
        if ( ! isset ($data["ts"]) || ! isset ($data["colId"]) || ! isset ($data["type"]))
            throw new \InvalidArgumentException("Cant decode binary message '$binary'");

        switch ($data["type"]) {

            case self::TYPE_NULL:
                $value = null;
                break;
            case self::TYPE_FLOAT:
                $value = $data["floatVal"];
                break;
            case self::TYPE_STRING:
                $value = $data["strVal"];
                break;
            default:
                throw new \InvalidArgumentException("Invalid value Type '{$data["type"]}");
        }

        return [
            "ts" => $data["ts"],
            "colId" =>$data["colId"],
            "val" => $value
        ];
    }
}
