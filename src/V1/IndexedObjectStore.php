<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 09.10.18
 * Time: 13:08
 */

namespace Talpa\BinFmt\V1;


use Phore\ObjectStore\Driver\ObjectStoreDriver;
use Phore\ObjectStore\ObjectStore;

class IndexedObjectStore
{

    private $objectNameMapper;

    public function __construct(ObjectStoreDriver $objectStoreDriver)
    {
        parent::__construct($objectStoreDriver);
        $this->objectNameMapper
    }


    public function pushFileIndexed(string $tmid, int $timestamp, $data)
    {

    }

    public function getDailyDataIndex(int $timestamp) : TDataIndex
    {

    }

    public function getFileIndexed(string $tmid, int $timestamp)
    {

    }

}
