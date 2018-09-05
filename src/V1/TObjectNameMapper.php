<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 12:27
 */

namespace Talpa\BinFmt\V1;


class TObjectNameMapper
{

    private $maschineId;

    public function __construct(string $maschineId)
    {
        $this->maschineId = $maschineId;
    }

    private function getIndex($ts)
    {
        return substr(md5($ts), 0, 6);
    }

    public function getMachinePath()
    {
        return $this->getIndex($this->maschineId) . "-" . $this->maschineId;
    }

    public function RawStoreBin(int $ts)
    {
        return "{$this->getMachinePath()}/full/{$this->getIndex($ts)}-$ts.full.bin";
    }

    public function RawStoreBinSampled(int $ts, int $interval)
    {
        return "{$this->getMachinePath()}/aggregate/{$this->getIndex($ts)}-$ts.$interval.bin";
    }

    public function ColumnIndex ()
    {
        return "{$this->getMachinePath()}/column.index.json";
    }

    public function DataIndex (int $timestamp)
    {

        return "{$this->getMachinePath()}/di/$timestamp.json";
    }

}
