<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 05.09.18
 * Time: 14:25
 */

namespace Talpa\BinFmt\V1;


use Talpa\BinFmt\TalpaMaschineStore;

class TDataIndex
{

    private $index;

    private $ts;

    public function __construct(array $dataIndex, int $timestamp)
    {
        $this->index = $dataIndex;
        $this->ts = $timestamp;
    }


    public function putTs(int $ts)
    {
        $ts = ((int)($ts / TMachineWriter::STORE_INTERVAL)) * TMachineWriter::STORE_INTERVAL;
        if ( ! isset($this->index[$ts]))
            $this->index[$ts] = 0;
        $this->index[$ts]++;
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
