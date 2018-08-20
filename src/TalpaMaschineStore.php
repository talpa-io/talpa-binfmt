<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 13:43
 */

namespace Talpa\BinFmt;


use Phore\CloudStore\Driver\CloudStoreDriver;
use Phore\CloudStore\ObjectStore;

class TalpaMaschineStore extends ObjectStore
{

    private $maschineId = null;
    /**
     * @var TalpaObjectName
     */
    private $names;


    public function __construct(CloudStoreDriver $cloudStoreDriver)
    {
        parent::__construct($cloudStoreDriver);
        $this->names = new TalpaObjectName();
    }

    public function setMaschineId (string $maschineId)
    {
        $this->maschineId = $maschineId;
    }


    public function getColumnIndex() : ColumnIndex
    {
        $oid = $this->names->ColumnIndex($this->maschineId);
        if ( ! $this->has($oid))
            return new ColumnIndex([]);
        return new ColumnIndex($this->getJson($oid));
    }

    public function putColumnIndex(ColumnIndex $columnIndex)
    {
        $oid = $this->names->ColumnIndex($this->maschineId);
        $this->putJson($oid, $columnIndex->getArray());
    }

    public function hasBinMaschineData(float $timestamp) : bool
    {
        $slotIntervalTs = (new SlotInterval())->getRawDataSlotId($timestamp);
        $oid = $this->names->RawStoreBin($this->maschineId, $slotIntervalTs);
        return $this->has($oid);
    }


    public function putBinMaschineData(float $timestamp, BinStreamWriter $binData)
    {
        $slotIntervalTs = (new SlotInterval())->getRawDataSlotId($timestamp);
        $oid = $this->names->RawStoreBin($this->maschineId, $slotIntervalTs);
        $this->put($oid, $binData->getData());
    }


}
