<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 15:44
 */

namespace Talpa\BinFmt\V1;


use Phore\FileSystem\FileStream;
use Phore\FileSystem\PhoreFile;

class TCLDataWriter extends TBinFmt
{

    /**
     * @var FileStream
     */
    private $fileStream;

    private $timestamp = null;

    private $colLastData = [];

    private $colNameIdMap = [];

    private $stats = [
        "strings" => 0,
        "numeric" => 0,
        "no_change" => 0,
        "time_shift" => 0,
        "time_set" => 0,
        "basic_data" => 0,
        "col_assign" => 0
    ];

    private $lastDataType;

    private $numRows = 0;

    const OUTPUT_BUFFER_LENGTH = 128000; // Optimal (for file usage)
    const GZ_COMPRESSION_LEVEL = 7; //Optimal

    const DEBUG_SKIP_COMPRESS_OUTPUT = false; // No-Debug: false  - if true will skip compession


    private $outputBuffer = "";


    public function __construct(FileStream $fileStream, array $metadata=[])
    {
        $this->fileStream = $fileStream;
        $this->writeFrame(self::TYPE_FILE_VERSION, 1);
        $serialized = json_encode($metadata);
        $this->writeVarCharFrame($serialized);
    }


    private function flushOutputBuffer()
    {
        if (self::DEBUG_SKIP_COMPRESS_OUTPUT) {
            $compressed = $this->outputBuffer;
        } else {
            $compressed = gzencode($this->outputBuffer, self::GZ_COMPRESSION_LEVEL);
        }

        $this->fileStream->fwrite(pack("L", strlen($compressed)) . $compressed);
        $this->outputBuffer = "";
    }

    private function write($data)
    {
        $this->outputBuffer .= $data;
        if (strlen($this->outputBuffer) > self::OUTPUT_BUFFER_LENGTH) {
            $this->flushOutputBuffer();
        }
    }

    private function writeFrame(int $frameType, int $colId)
    {

        //$this->write(pack("CS", $frameType, $colId));
        $data = chr($frameType) . $this->int2binary($colId);

        if (strlen($data) !== 3)
            throw new \InvalidArgumentException("length");
        $this->write($data);
    }


    private function detectType ($value)
    {
        if (is_numeric($value)) {
            if (preg_match ("/^([0-9]+)\.0+$/", $value, $matches)) {
                $value = (int)$matches[1];
            }
            $value = (float)$value;
           // phore_log("$value");
            if (strpos($value, ".") !== false) {
                $decimalPlaces = (strlen($value) - strpos($value, ".")) - 1;
                $digitCount = strlen(str_replace("-", "", $value)) - $decimalPlaces - 1;
                $value = floatval($value);
                // Don't trust the last digit of floats...
                if ($value > -3200 && $value < 3200 && $decimalPlaces <= 1) {
                    return self::TYPE_MIN1_FLOAT; // 2 byte
                }
                if ($value > -320 && $value < 320 && $decimalPlaces <= 2) {
                    return self::TYPE_MIN2_FLOAT; // 2 byte
                }
                if ($value > -32 && $value < 32 && $decimalPlaces <= 3) {
                    return self::TYPE_MIN3_FLOAT; // 2 byte
                }


                if ($value >= -211000 && $value <= 211000 && $decimalPlaces <= 4) {
                    return self::TYPE_MED_FLOAT; // 4 byte
                }
                if ($digitCount + $decimalPlaces < 9)
                    return self::TYPE_FLOAT; // 4 byte
                return self::TYPE_DOUBLE; // 8 byte
            }
            if ($value < 0) {
                // Singed value
                $value = $value * -1;

                if ($value >= 4294967296)
                    return self::TYPE_INT64_NEG;
                if ($value >= 65536)
                    return self::TYPE_INT32_NEG;
                if ($value >= 255)
                    return self::TYPE_INT16_NEG;
                return self::TYPE_INT8_NEG;
            }
            if ($value >= 4294967296)
                return self::TYPE_INT64;
            if ($value >= 65536)
                return self::TYPE_INT32;
            if ($value >= 255)
                return self::TYPE_INT16;
            if ($value >= 200)
                return self::TYPE_INT8;
            return (int)$value; // Return the value 0-200
        }

        if ($value === "")
            return self::TYPE_EMPTY_STRING;
        if ($value === null)
            return self::TYPE_NULL;
        if ($value === true)
            return self::TYPE_TRUE;
        if ($value === false)
            return self::TYPE_FALSE;


        return self::TYPE_STRING;

    }

    private function castValue(int $type, $value)
    {
        if (isset (self::TYPE_MAP[$type])) {
            $curType = self::TYPE_MAP[$type];
            $casted = $value * $curType[2];
            return $casted;
        }
        return $value;
    }

    private function writePayload(int $type, $value)
    {
        if (isset (self::TYPE_MAP[$type])) {
            $curType = self::TYPE_MAP[$type];
            $this->write(pack($curType[0], $value));
            return;
        }
        if ($type === self::TYPE_STRING) {
            $this->write($value);
            return;
        }
        throw new \InvalidArgumentException("Invalid payload type '$type'");
    }


    private function writeVarCharFrame (string $data)
    {
        if (strlen($data) >= 64000)
            throw new \InvalidArgumentException("VarChar data frame is to big: limit 64000 byte.");
        $enc = $data;
        $this->write(pack("S", strlen($enc)) .  $enc);
    }


    private function writeColIdAssign(int $colId, string $columnName)
    {
        $this->writeFrame(self::TYPE_NAME_ASSIGN, $colId);
        $this->stats["col_assign"]++;
        $columnNamePacked = $columnName; //gzencode($columnName, 5);
        $len = strlen($columnNamePacked);
        if ($len > 255)
            throw new \InvalidArgumentException("Column name '$columnName' too long (max size: 255 byte)");
        $this->write(pack("c", $len));
        $this->write($columnName);
    }


    public function inject(float $timestamp, string $columnName, $value, string $measureUnit=null)
    {
        $this->numRows++;

        if ($measureUnit !== null)
            $columnName .= ":$measureUnit";

        if ( ! isset ($this->colNameIdMap[$columnName])) {
            $newColId = count(array_keys($this->colNameIdMap));
            $this->colNameIdMap[$columnName] = $newColId;
            $this->writeColIdAssign($newColId, $columnName);
        }
        $colId = $this->colNameIdMap[$columnName];

        $timestamp = (int)($timestamp * self::TS_MULTIPLY);
        if ($this->timestamp === null || ($timestamp - $this->timestamp) > 65000) {
            // Max 2 Byte for Time Shift
            $this->writeFrame(self::TYPE_SET_TIMESTAMP, 0);
            $this->writePayload(self::TYPE_INT64, $timestamp);
            $this->timestamp = $timestamp;
            $this->stats["time_set"]++;

        } else if ($this->timestamp !== $timestamp) {
            $this->writeFrame(self::TYPE_SHIFT_TIMESTAMP, ($timestamp - $this->timestamp));
            $this->timestamp = $timestamp;
            $this->stats["time_shift"]++;
        } else {
            //echo "N: " . $timestamp . ";";
        }
        $type = $this->lastDataType = $this->detectType($value);
        $value = $this->castValue($type, $value);

        if (isset ($this->colLastData[$colId])) {
            if ($this->colLastData[$colId] === $value) {
                $this->writeFrame(self::TYPE_UNMODIFIED, $colId);
                $this->stats["no_change"]++;
                return true;
            }
        }

        if ($type <= 204) {
            // $type is $value for values < 200 201,202,203 => basic types
            $this->colLastData[$colId] = $type;
            $this->writeFrame($type, $colId);
            $this->stats["basic_data"]++;
            return true;
        }

        if ($type === self::TYPE_STRING) {
            $this->colLastData[$colId] = $value;
            $this->writeFrame(self::TYPE_STRING, $colId);
            $this->writeVarCharFrame($value);
            $this->stats["strings"]++;
            return true;
        }

        $this->colLastData[$colId] = $value;
        $this->writeFrame($type, $colId);
        $this->writePayload($type, $value);
        $this->stats["numeric"]++;
        return true;
    }


    public function getLastDataType () : int
    {
        return $this->lastDataType;
    }


    public function getStats() : array
    {
        $stats = $this->stats;
        $stats["col_name_count"] = count(array_keys($this->colNameIdMap));
        return $stats;
    }

    public function close() : PhoreFile
    {

        $this->writeFrame(self::TYPE_EOF, 0);
        $this->writePayload(self::TYPE_INT64, $this->numRows);
        $this->flushOutputBuffer();
        return $this->fileStream->fclose();
    }


}
