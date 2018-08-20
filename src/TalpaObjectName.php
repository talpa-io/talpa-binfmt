<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 12:27
 */

namespace Talpa\BinFmt;


class TalpaObjectName
{

    public function RawStoreCsv(string $maschineId, int $ts)
    {
        return "$maschineId/$ts";
    }

    public function RawStoreBin(string $maschineId, int $ts)
    {
        return "$maschineId/$ts.bin";
    }

    public function ColumnIndex (string $maschineId)
    {
        return "$maschineId/column.index.json";
    }

    public function ShiftIndex (string $maschineId, int $shiftTs)
    {
        return "$maschineId/shift.$shiftTs.json";
    }

}
