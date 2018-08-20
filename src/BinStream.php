<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 10:42
 */

namespace Talpa\BinFmt;


class BinStream
{

    /**
     * @var ColumnIndex
     */
    private $columnIndex;

    private $dataFormat;

    private $data = "";

    public function __construct(ColumnIndex $columnIndex)
    {
        $this->columnIndex = $columnIndex;
        $this->dataFormat = new TalpaDataFormat();
    }

    public function write (float $ts, string $colName, string $measureUnit, $value)
    {
        $colId = $this->columnIndex->checkRow($colName, $ts, $measureUnit, $value);
        $this->data .= $this->dataFormat->pack($ts, $colId, $value) . "\n";

    }


    public function getData () : string
    {
        return $this->data;
    }

}
