<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 05.09.18
 * Time: 14:25
 */

namespace Talpa\BinFmt\V1;


class TDataIndex
{

    private $index;

    private $ts;

    public function __construct(array $dataIndex, int $timestamp)
    {
        $this->index = $dataIndex;
        $this->ts = $timestamp;
    }


    public function putTs(int $fromTs, int $tillTs)
    {
        $this->index[$fromTs] = $tillTs;
    }

    public function getArray() : array
    {
        return $this->index;
    }

    public function getTimestamp () : int
    {
        return $this->ts;
    }
}
