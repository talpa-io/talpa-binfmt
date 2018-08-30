<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 30.08.18
 * Time: 11:04
 */

namespace Talpa\BinFmt\V1;


use Google\Cloud\Storage\StorageClient;

class TMachine
{

    const BUCKET = "talpa-machine-a";

    private $machineId;
    private $bucket;

    public function __construct(string $machineId, string $authKeyFile)
    {
        $store = new StorageClient([
            "keyFilePath" => $authKeyFile
        ]);
        $this->machineId = $machineId;
        $this->bucket = $store->bucket(self::BUCKET);
    }



    public function set





}
