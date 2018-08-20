<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 15:10
 */

namespace Talpa\BinFmt;


class BinStreamReader
{

    private $data;

    /**
     * @var ColumnIndex
     */
    private $colIndex;

    public function __construct(ColumnIndex $columnIndex)
    {
        $this->colIndex = $columnIndex;
    }


    public function setData( $data)
    {
        $this->data = unserialize(gzdecode($data));
    }


    public function each (callable $fn) {
        $format = new TalpaDataFormat();
        foreach ($this->data as $line) {
            $line = $format->unpack($line);
            $fn($line["ts"], $this->colIndex->getColumnNameById($line["colId"]), $line["val"], $this->colIndex);
        }
    }


}
