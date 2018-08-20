<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 10:34
 */

namespace Talpa\BinFmt;


class ColumnIndex
{

    private $columnIndex = [];
    private $colIdToNameIdx = [];

    public function __construct(array $columnIndex)
    {
        $this->columnIndex = $columnIndex;
        foreach ($this->columnIndex as $colName => $columnIndex) {
            $this->colIdToNameIdx[$columnIndex["id"]] = $colName;
        }
    }


    public function checkRow (string $colName, float $ts, string $measureUnit, $value) : int
    {
        $columnIndex =& $this->columnIndex;

        if ( ! isset ($columnIndex[$colName])) {
            $columnIndex[$colName] = [
                "id" => count(array_keys($columnIndex))+1,
                "first_seen_ts" => $ts,
                "last_seen_ts" => $ts,
                "measure_unit_cur" => null,
                "measure_unit_all" => [],
                "min" => null,
                "max" => null
            ];
            $this->colIdToNameIdx[$columnIndex[$colName]["id"]] = $colName;
        }
        if ($ts > $columnIndex[$colName]["last_seen_ts"])
            $columnIndex[$colName]["last_seen_ts"] = $ts;

        if ($columnIndex[$colName]["measure_unit_cur"] !== $ts) {
            $columnIndex[$colName]["measure_unit_all"][$measureUnit] = time();
        }
        $columnIndex[$colName]["measure_unit_cur"] = $measureUnit;

        if ($value < $columnIndex[$colName]["min"] || $columnIndex[$colName]["min"] === null) {
            $columnIndex[$colName]["min"] = $value;
        }
        if ($value > $columnIndex[$colName]["max"] || $columnIndex[$colName]["max"] === null) {
            $columnIndex[$colName]["max"] = $value;
        }
        return $columnIndex[$colName]["id"];
    }

    public function getColumnIndex() : array
    {
        return $this->columnIndex;
    }

}
