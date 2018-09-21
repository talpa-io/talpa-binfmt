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
        "basic_data" => 0
    ];

    private $numRows = 0;

    const OUTPUT_BUFFER_LENGTH = 128000; // Optimal (for file usage)
    const GZ_COMPRESSION_LEVEL = 7; //Optimal

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
        $compressed = gzencode($this->outputBuffer, self::GZ_COMPRESSION_LEVEL);
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
            if (strpos($value, ".") !== false) {
                $value = floatval($value);
                if ($value > 99 || $value < 99)
                    return self::TYPE_FLOAT;
                return self::TYPE_DOUBLE;
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
            return $value * $curType[2];
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
        $columnNamePacked = $columnName; //gzencode($columnName, 5);
        $len = strlen($columnNamePacked);
        if ($len > 255)
            throw new \InvalidArgumentException("Column name '$columnName' too long (max size: 255 byte)");
        $this->write(pack("c", $len));
        $this->write($columnName);
    }


    public function inject(float $timestamp, string $columnName, $value)
    {
        $this->numRows++;

        if ( ! isset ($this->colNameIdMap[$columnName])) {
            $newColId = count(array_keys($this->colNameIdMap));
            $this->colNameIdMap[$columnName] = $newColId;
            $this->writeColIdAssign($newColId, $columnName);
        }
        $colId = $this->colNameIdMap[$columnName];

        $timestamp = (int)($timestamp * self::TS_MULTIPLY);
        if ($this->timestamp === null || ($timestamp - $this->timestamp) > 64 * self::TS_MULTIPLY) {
            $this->writeFrame(self::TYPE_SET_TIMESTAMP, 0);
            $this->writePayload(self::TYPE_INT64, $timestamp);
            $this->timestamp = $timestamp;
            $this->stats["time_set"]++;

        } else if ($this->timestamp !== $timestamp) {
            $this->writeFrame(self::TYPE_SHIFT_TIMESTAMP, ($timestamp - $this->timestamp));
            $this->timestamp = $timestamp;
            $this->stats["time_shift"]++;
        }
        $type = $this->detectType($value);
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
