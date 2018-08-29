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

    private function getIndex($ts)
    {
        return substr(md5($ts), 0, 6);
    }

    public function RawStoreCsv(string $maschineId, int $ts)
    {
        return "$maschineId/$ts";
    }

    public function RawStoreBin(string $maschineId, int $ts)
    {
        return "{$this->getIndex($maschineId)}-$maschineId/{$this->getIndex($ts)}-$ts.all.bin";
    }

    public function RawStoreBinMin(string $maschineId, int $ts)
    {
        return "{$this->getIndex($maschineId)}-$maschineId/{$this->getIndex($ts)}-$ts.sec.bin";
    }

    public function ColumnIndex (string $maschineId)
    {
        return "{$this->getIndex($maschineId)}-$maschineId/column.index.json";
    }

    public function ShiftIndex (string $maschineId, int $shiftTs)
    {
        return "{$this->getIndex($maschineId)}-$maschineId/shift.$shiftTs.json";
    }

}
