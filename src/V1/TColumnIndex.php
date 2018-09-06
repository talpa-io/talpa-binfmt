<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 10:34
 */

namespace Talpa\BinFmt\V1;


class TColumnIndex
{

    private $columnIndex = [];
    private $colIdToNameIdx = [];
    private $isModified = false;



    public function __construct(array $columnIndex = null)
    {
        if ($columnIndex === null)
            $columnIndex = [];
        $this->columnIndex = $columnIndex;
        foreach ($this->columnIndex as $colName => $columnIndex) {
            $this->colIdToNameIdx[$columnIndex["id"]] = $colName;
        }
    }

    public function checkRow (float $ts, string $colName, string $measureUnit, $value) : int
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
            $this->isModified = true;
        }
        if ($ts > $columnIndex[$colName]["last_seen_ts"]) {
            $columnIndex[$colName]["last_seen_ts"] = $ts;
            $this->isModified = true;
        }

        if ($columnIndex[$colName]["measure_unit_cur"] !== $ts) {
            $columnIndex[$colName]["measure_unit_all"][$measureUnit] = $ts;
            $this->isModified = true;
        }
        if ($columnIndex[$colName]["measure_unit_cur"] !== $measureUnit) {
            $columnIndex[$colName]["measure_unit_cur"] = $measureUnit;
            $this->isModified = true;
        }

        if ($value < $columnIndex[$colName]["min"] || $columnIndex[$colName]["min"] === null) {
            $columnIndex[$colName]["min"] = $value;
            $this->isModified = true;
        }
        if ($value > $columnIndex[$colName]["max"] || $columnIndex[$colName]["max"] === null) {
            $columnIndex[$colName]["max"] = $value;
            $this->isModified = true;
        }
        return $columnIndex[$colName]["id"];
    }

    public function getColumnNameById (int $id) : string
    {
        if ( ! isset($this->colIdToNameIdx[$id]))
            throw new \Exception("Invalid column id '$id'");
        return $this->colIdToNameIdx[$id];
    }

    public function getColumnIdByName(string $colName) : int
    {
        if ( ! isset($this->columnIndex[$colName]))
            throw new \Exception("Invalid column name '$colName'");
        return $this->columnIndex[$colName]["id"];
    }

    public function isModified() : bool
    {
        return $this->isModified;
    }

    public function getArray() : array
    {
        return $this->columnIndex;
    }

}
