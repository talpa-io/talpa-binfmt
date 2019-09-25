<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 30.08.18
 * Time: 13:37
 */

namespace Talpa\BinFmt\V1;


use Phore\FileSystem\GzFileStream;
use Phore\FileSystem\PhoreTempFile;

class TMachineInputMux
{

    private $columnIndex;
    private $muxData = [];
    private $mainWriter;
    private $onCloseFn;

    public function __construct(array $availMux, TColumnIndex $columnIndex, callable $onCloseFn)
    {
        $this->onCloseFn = $onCloseFn;
        $this->columnIndex = $columnIndex;
        $this->mainWriter = new TCLDataWriter(new GzFileStream(new PhoreTempFile("full"), "w"));
        foreach ($availMux as $curMux) {
            $this->muxData[$curMux] = [
                "writer" => new TCLDataWriter(new GzFileStream(new PhoreTempFile("mux-$curMux-"), "w")),
                "data" => [],
                "ts" => null
            ];
        }
    }




    private function injectValueMux (int $mux, float $timestamp, int $columnId, $value) {
        $timestamp = ((int)($timestamp/$mux)) * $mux;
        if ($timestamp !== $this->muxData[$mux]["ts"]) {
            if ($this->muxData[$mux]["ts"] !== null) {
                foreach ($this->muxData[$mux]["data"] as $_colId => $_val) {
                    $this->muxData[$mux]["writer"]->inject($this->muxData[$mux]["ts"], $_colId, $_val);
                }
            }
            $this->muxData[$mux]["data"] = [];
            $this->muxData[$mux]["ts"] = $timestamp;
        }
        $this->muxData[$mux]["data"][$columnId] = $value;
    }


    public function injectValue (float $timestamp, string $columnName, string $measureUnit, $value)
    {
        $colId = $this->columnIndex->checkRow($timestamp, $columnName, $measureUnit, $value);

        $this->mainWriter->inject($timestamp, $colId, $value);
        foreach ($this->muxData as $key => $mux) {
            $this->injectValueMux($key, $timestamp, $colId, $value);
        }
    }

    /**
     *
     */
    public function close()
    {
        $file = $this->mainWriter->close();
        ($this->onCloseFn)($file, null);

        foreach ($this->muxData as $muxId => $mux) {
            $file = $this->muxData[$muxId]["writer"]->close();
            ($this->onCloseFn)($file, $muxId);
        }
    }

}
