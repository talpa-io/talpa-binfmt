<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 17.12.18
 * Time: 13:17
 */

namespace Talpa\BinFmt\Test\Unit;


use Phore\FileSystem\PhoreTempFile;
use PHPUnit\Framework\TestCase;
use Talpa\BinFmt\V1\TCLDataReader;
use Talpa\BinFmt\V1\TCLDataWriter;

class DataReaderTest extends TestCase
{


    public function testDataReaderTimeShift()
    {
        $tmpFile = new PhoreTempFile();
        $dataWriter = new TCLDataWriter($stream = $tmpFile->fopen("w"));


        $dataWriter->inject(1543881205.009254, "col1:", 2);
        $dataWriter->inject(1543881205.009255, "col1:", 2);
        $dataWriter->inject(1543881205.0093, "col1:", 3);
        $dataWriter->inject(1543881205.0193, "col1:", 4);

        $dataWriter->close();

        $reader = new TCLDataReader();
        $ret = [];
        $reader->setOnDataCb(function ($ts, $col, $val) use (&$ret) {
            $ret[] = [$ts, $col, $val];
        });

        $reader->parse($tmpFile->fopen("r"));
        print_r ($ret);
        $this->assertEquals(1543881205.0092, $ret[0][0]);
        $this->assertEquals(1543881205.0092, $ret[1][0]);
        $this->assertEquals(1543881205.0093, $ret[2][0]);
        $this->assertEquals(1543881205.0193, $ret[3][0]);
    }


}
