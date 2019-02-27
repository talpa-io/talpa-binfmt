<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 19:40
 */

namespace Talpa\BinFmt\V1;


use Phore\FileSystem\FileStream;

class TCLDataReader extends TBinFmt
{

    private $fileStream;
    private $curTsInt = null;
    private $curTsFloat = 0.;

    private $lastColData = null;

    private $rowCount = 0;

    private $buffer = "";
    private $bLen = 0;
    private $bIndex = 0;
    private $pullMore;


    public function __construct()
    {

        $this->pullMore = function() {
            if ($this->fileStream->feof())
                throw new \InvalidArgumentException("End of input stream before eof data frame packet recieved. File corruption.");
            $data = $this->fileStream->fread(4);
            $bytesToRead = unpack("Llen", $data)["len"];

            $buf = $this->fileStream->fread($bytesToRead);
            while (strlen($buf) < $bytesToRead) {
                usleep(500);
                if ($this->fileStream->feof())
                    throw new \InvalidArgumentException("Broken input pipe.");
                $buf .= $this->fileStream->fread($bytesToRead - strlen($buf));

            }

            $this->buffer = gzdecode($buf);
            if ($this->buffer === false)
                throw new \InvalidArgumentException("Gzip error: Cannot decode data frame: $buf");

            $this->bLen = strlen($this->buffer);
        };
    }


    /**
     * @return array
     */
    public function getStats() {
        return [
            "row_count" => $this->rowCount
        ];
    }

    private function read(int $length)
    {
        // strlen is too slow here - use bLen as buffer len
        if ($this->bLen < $this->bIndex + $length) {
            $this->buffer = substr($this->buffer, $this->bIndex);
            $this->bIndex = 0;
            ($this->pullMore)();
        }

        $data =  substr($this->buffer, $this->bIndex, $length);
        $this->bIndex += $length;
        return $data;
    }


    private function readDataFrame(&$type, &$colId)
    {
        $input = $this->read(3);

        $type = ord($input[0]);
        //Shortcut for bin2int
        $colId = ord($input[1]) * 255 + ord($input[2]);
        //$data = unpack("Ctype/ScolId", $this->read(3));
        //return $data;
    }

    private function readPayload($type)
    {
        if (isset (self::TYPE_MAP[$type])) {
            $curType = self::TYPE_MAP[$type];
            $data = unpack($curType[0], $this->read($curType[1]));
            $value = $data[1] / $curType[2];

            if ($type == self::TYPE_MIN3_FLOAT)
                $value = round($value, 3);
            if ($type == self::TYPE_MIN2_FLOAT)
                $value = round($value, 2);
            if ($type == self::TYPE_MIN1_FLOAT)
                $value = round($value, 1);

            if ($type == self::TYPE_MED_FLOAT)
                $value = round($value, 5);

            if ($type == self::TYPE_FLOAT) {
                $decimals = strpos(str_replace("-", "", $value), ".");
                $value = round($value, (8-$decimals));
            }

            return $value;
        }
        if ($type === self::TYPE_STRING) {
            //echo "string";
            $header = unpack("S", $this->read(2));
            $data = $this->read($header[1]);
            return $data;
        }
        throw new \InvalidArgumentException("Invalid payload type '$type'");
    }

    private $metadata;

    private $onDataCb;

    private $colIdToNameMap = [];

    public function setOnDataCb(callable $cb)
    {
        $this->onDataCb = $cb;
    }

    public function parse(FileStream $fileStream, array $includeCols = [])
    {
        $this->fileStream = $fileStream;
        $this->rowCount = 0;
        $this->readDataFrame($type, $colId);
        if ($type !== self::TYPE_FILE_VERSION || $colId !== 1)
            throw new \InvalidArgumentException("Unknown file format. Requires talpa binfmt v1");
        $this->metadata = json_decode($this->readPayload(self::TYPE_STRING));

        $includeIds = [];
        while (true) {
            $this->readDataFrame($type, $colId);

            if ($type <= 204) {
                $value = $type;
                if ($type === self::TYPE_EMPTY_STRING)
                    $value = "";
                if ($type === self::TYPE_NULL)
                    $value = null;
                if ($type === self::TYPE_FALSE)
                    $value = false;
                if ($type === self::TYPE_TRUE)
                    $value = true;
                $this->lastColData[$colId] = $value;
                if (isset ($includeIds[$colId]))
                    ($this->onDataCb)($this->curTsFloat, $this->colIdToNameMap[$colId][0], $value, $this->colIdToNameMap[$colId][1]);
                $this->rowCount++;
                continue;
            }

            if ($type == self::TYPE_UNMODIFIED) {
                if (isset ($includeIds[$colId]))
                    ($this->onDataCb)($this->curTsFloat, $this->colIdToNameMap[$colId][0], $this->lastColData[$colId], $this->colIdToNameMap[$colId][1]);
                $this->rowCount++;
                continue;
            }

            if ($type == self::TYPE_SHIFT_TIMESTAMP) {
                $this->curTsInt += $colId;
                $this->curTsFloat = $this->curTsInt / self::TS_MULTIPLY;
                continue;
            }

            if ($type == self::TYPE_SET_TIMESTAMP) {
                $newTs = $this->readPayload(self::TYPE_INT64);
                $this->curTsInt = $newTs;
                $this->curTsFloat = $newTs / self::TS_MULTIPLY;
                continue;
            }

            if ($type == self::TYPE_NAME_ASSIGN) {
                $len = unpack("clen", $this->read(1))["len"];
                $colName = $this->read($len);
                $colExp = explode(":", $colName);
                if (count($colExp) == 1)
                    $colExp[] = "";
                $this->colIdToNameMap[$colId] = $colExp;
                if (in_array($colExp[0], $includeCols) || empty($includeCols))
                    $includeIds[$colId] = true;
                continue;
            }



            if ($type == self::TYPE_EOF) {
                $rowCount = $this->readPayload(self::TYPE_INT64);
                if ($rowCount !== $this->rowCount)
                    throw new \InvalidArgumentException("Row count mismatch: Should be '$rowCount' but actual '{$this->rowCount}'");
                break;
            }

            $value = $this->readPayload($type);
            $this->lastColData[$colId] = $value;
            if (isset ($includeIds[$colId]))
                ($this->onDataCb)($this->curTsFloat, $this->colIdToNameMap[$colId][0], $value, $this->colIdToNameMap[$colId][1]);
            $this->rowCount++;

        }

    }

}
