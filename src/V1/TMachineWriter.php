<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 30.08.18
 * Time: 11:04
 */

namespace Talpa\BinFmt\V1;


use Phore\CloudStore\NotFoundException;
use Phore\CloudStore\ObjectStore;
use Phore\FileSystem\PhoreFile;

class TMachineWriter
{

    const BUCKET = "talpa-data-a";

    private $objectNameMapper;

    private $objectStore;

    const SAMPLE_WRITERS = [
        1, 2, 4, 8, 15, 30, 60, 320
    ];

    public function __construct(string $machineId, ObjectStore $objectStore)
    {
        $this->objectNameMapper = new TObjectNameMapper($machineId);
        $this->objectStore = $objectStore;
    }



    public function openInterval(int $interval) : TMachineInputMux
    {
        try {
            $columnIndex = new TColumnIndex($this->objectStore->getJson($this->objectNameMapper->ColumnIndex()));
            phore_log()->info("Column-Index successfully loaded.");
        } catch (NotFoundException $e) {
            phore_log()->info("No Column-Index found. Starting new one (new machine?)");
            $columnIndex = new TColumnIndex();
        }


        $closeFn = function (PhoreFile $tmpFile, int $sampleInterval = null) use ($interval, $columnIndex) {
            if ($sampleInterval === null) {
                if ($columnIndex->isModified()) {
                    phore_log("Column-index was modified: Uploading current version.");
                    $this->objectStore->putJson($this->objectNameMapper->ColumnIndex(), $columnIndex->getArray());

                }
                phore_log("Uploading maschine full data (" . $tmpFile->fileSize() . "byte)");
                $this->objectStore->putStream($this->objectNameMapper->RawStoreBin($interval), $tmpFile->fopen("r")->getRessource());
                $tmpFile->unlink();
            } else {
                phore_log("Uploading sampled data (Interval '$sampleInterval'seconds... (Size {$tmpFile->fileSize()})");
                $this->objectStore->putStream(
                    $this->objectNameMapper->RawStoreBinSampled($interval, $sampleInterval),
                    $tmpFile->fopen("r")->getRessource()
                );
                $tmpFile->unlink();
            }
        };

        return new TMachineInputMux(self::SAMPLE_WRITERS, $columnIndex, $closeFn);
    }

}
