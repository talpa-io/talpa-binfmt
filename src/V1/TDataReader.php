<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.08.18
 * Time: 19:40
 */

namespace Talpa\BinFmt\V1;


use Phore\FileSystem\FileStream;

class TDataReader extends TBinFmt
{

    private $fileStream;
    private $curTs = null;

    private $lastColData = null;

    private $rowCount = 0;

    private $buffer = "";
    private $bIndex = 0;
    private $pullMore;


    public function __construct(FileStream $fileStream)
    {
        $this->fileStream = $fileStream;
        $this->pullMore = function() {
            if ($this->fileStream->feof())
                throw new \InvalidArgumentException("End of input stream before eof data frame packet recieved. File corruption.");
            $this->buffer .= $this->fileStream->fread(8000);
        };
    }


    private function read(int $length)
    {
        if (strlen($this->buffer) < $this->bIndex + $length) {
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
        if ($input)
        $type = ord($input[0]);
        $colId = $this->bin2int(substr($input, 1));
        //$data = unpack("Ctype/ScolId", $this->read(3));
        //return $data;
    }

    private function readPayload($type)
    {
        if (isset (self::TYPE_MAP[$type])) {
            $curType = self::TYPE_MAP[$type];
            $data = unpack($curType[0], $this->read($curType[1]));
            return $data[1] / $curType[2];
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

    public function setOnDataCb(callable $cb)
    {
        $this->onDataCb = $cb;
    }

    public function parse(array $includeIds = [])
    {

        //echo "First frame:";
        $data = $this->readDataFrame($type, $colId);
        if ($type !== self::TYPE_FILE_VERSION || $colId !== 1)
            throw new \InvalidArgumentException("Unknown file format. Requires talpa binfmt v1");
        $this->metadata = json_decode($this->readPayload(self::TYPE_STRING));
        //echo "\nMetadtat";

        while (true) {
            //sleep (1);

            $this->readDataFrame($type, $colId);
            //$frame = $this->readDataFrame();
            //$type = $frame["type"];
            //$colId = $frame["colId"];
            if ($type <= 204) {
               // echo "\nBASIC VALUE $type";
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
                ($this->onDataCb)($this->curTs, $colId, $value);
                $this->rowCount++;
                continue;
            }
            if ($type == self::TYPE_SET_TIMESTAMP) {
               // echo "\nSET_TIMESTAMP";
                $newTs = $this->readPayload(self::TYPE_INT64) / self::TS_MULTIPLY;
                $this->curTs = $newTs;
                continue;
            }
            if ($type == self::TYPE_SHIFT_TIMESTAMP) {
               // echo "\nSHIFT_TIMESTAMP";
                $this->curTs += $colId / self::TS_MULTIPLY;
                continue;
            }

            if ($type == self::TYPE_UNMODIFIED) {
                //echo "\nUNMODIFIED";
                ($this->onDataCb)($this->curTs, $colId, $this->lastColData[$colId]);
                $this->rowCount++;
                continue;
            }

            if ($type == self::TYPE_EOF) {
                //echo "\nEOF";
                $rowCount = $this->readPayload(self::TYPE_INT64);
                if ($rowCount !== $this->rowCount)
                    throw new \InvalidArgumentException("Row count mismatch: Should be '$rowCount' but actual '{$this->rowCount}'");
                break;
            }

            $value = $this->readPayload($type);
            $this->lastColData[$colId] = $value;
            ($this->onDataCb)($this->curTs, $colId, $value);
            $this->rowCount++;

        }

    }

}
