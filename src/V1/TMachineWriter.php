<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 30.08.18
 * Time: 11:04
 */

namespace Talpa\BinFmt\V1;


use Phore\Core\Exception\NotFoundException;
use Phore\ObjectStore\ObjectStore;
use Phore\FileSystem\PhoreFile;

class TMachineWriter
{

    const BUCKET = "talpa-data-a";

    const STORE_INTERVAL = 3600;
    const DATA_INDEX_INTERVAL = 3600 * 24 * 14; // 14 Days

    private $objectNameMapper;

    /**
     * @var ObjectStore
     */
    private $objectStore;

    const SAMPLE_WRITERS = [
        1, 2, 3, 4, 5, 8, 10, 15, 30, 60, 320
    ];

    public function __construct(string $machineId, ObjectStore $objectStore)
    {
        $this->objectNameMapper = new TObjectNameMapper($machineId);
        $this->objectStore = $objectStore;
    }



    public function openInterval(int $interval) : TMachineInputMux
    {
        if ($interval % self::STORE_INTERVAL !== 0)
            throw new \InvalidArgumentException("Interval '$interval' is no valid starting timestamp.");
        try {
            $columnIndex = new TColumnIndex($this->objectStore->object($this->objectNameMapper->ColumnIndex())->getJson());
            phore_log()->info("Column-Index successfully loaded.");
        } catch (NotFoundException $e) {
            phore_log()->info("No Column-Index found. Starting new one (new machine?)");
            $columnIndex = new TColumnIndex();
        }

        $machineDataTs = ((int)($interval / self::DATA_INDEX_INTERVAL)) * self::DATA_INDEX_INTERVAL;
        try {
            $machineData = new TDataIndex($this->objectStore->object($this->objectNameMapper->DataIndex($machineDataTs))->getJson(), $machineDataTs);
            phore_log()->info("Data-Index successfully loaded.");
        } catch (NotFoundException $e) {
            $machineData = new TDataIndex([], $machineDataTs);
            phore_log()->info("Created new Data-Index for $machineDataTs.");
        }


        $closeFn = function (PhoreFile $tmpFile, int $sampleInterval = null) use ($interval, $columnIndex, $machineData) {
            if ($sampleInterval === null) {
                if ($columnIndex->isModified()) {
                    phore_log("Column-index was modified: Uploading current version.");
                    $this->objectStore->object($this->objectNameMapper->ColumnIndex())->putJson($columnIndex->getArray());

                }
                phore_log("Uploading maschine full data (" . $tmpFile->fileSize() . "byte)");
                $this->objectStore->object(
                    $this->objectNameMapper->RawStoreBin($interval)
                )->putStream($tmpFile->fopen("r")->getRessource());
                $tmpFile->unlink();

                $machineData->putTs($interval);
                $this->objectStore->object($this->objectNameMapper->DataIndex($machineData->getTimestamp()))->putJson($machineData->getArray());

            } else {
                phore_log("Uploading sampled data (Interval '$sampleInterval'seconds... (Size {$tmpFile->fileSize()})");
                $this->objectStore->object(
                    $this->objectNameMapper->RawStoreBinSampled($interval, $sampleInterval)
                )->putStream($tmpFile->fopen("r")->getRessource());

                $tmpFile->unlink();
            }
        };

        return new TMachineInputMux(self::SAMPLE_WRITERS, $columnIndex, $closeFn);
    }

}
