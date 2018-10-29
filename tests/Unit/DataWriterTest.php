<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.10.18
 * Time: 13:45
 */

namespace Talpa\BinFmt\Test\Unit;


use Phore\FileSystem\PhoreTempFile;
use PHPUnit\Framework\TestCase;
use Talpa\BinFmt\V1\TCLDataReader;
use Talpa\BinFmt\V1\TCLDataWriter;

class DataWriterTest extends TestCase
{



    public function testTimeShifting()
    {
        $tmpFile = new PhoreTempFile();
        $w = new TCLDataWriter($tmpFile->fopen("w"));

        $w->inject(1, "col1", 1);
        $this->assertEquals(1, $w->getStats()["time_set"]);

        $w->inject(2, "col1", 1);
        $this->assertEquals(1, $w->getStats()["time_set"]);
        $this->assertEquals(1, $w->getStats()["time_shift"]);

        $w->inject(10, "col1", 1);
        $this->assertEquals(2, $w->getStats()["time_set"]);

        // Time shift > 6.5 Seconds: Use Time Set:
        $w->inject(16.51, "col1", 1);
        $this->assertEquals(3, $w->getStats()["time_set"]);
        $this->assertEquals(1, $w->getStats()["time_shift"]);
    }

    public function testUnchangedColumn()
    {
        $tmpFile = new PhoreTempFile();
        $w = new TCLDataWriter($tmpFile->fopen("w"));

        $w->inject(1, "col1", 1);
        $this->assertEquals(0, $w->getStats()["no_change"]);
        $this->assertEquals(1, $w->getStats()["basic_data"]);

        // If data unchanged: NO_CHANGE frame was written
        $w->inject(2, "col1", 1);
        $this->assertEquals(1, $w->getStats()["no_change"]);
        $this->assertEquals(1, $w->getStats()["basic_data"]);

        $w->inject(3, "col1", 2);
        $this->assertEquals(1, $w->getStats()["no_change"]);
        $this->assertEquals(2, $w->getStats()["basic_data"]);
    }

    public function testSettingColumnName()
    {
        $tmpFile = new PhoreTempFile();
        $w = new TCLDataWriter($tmpFile->fopen("w"));

        $w->inject(1, "col1", 1);
        $this->assertEquals(1, $w->getStats()["col_name_count"]);

        // If data unchanged: NO_CHANGE frame was written
        $w->inject(2, "col2", 1);
        $this->assertEquals(2, $w->getStats()["col_name_count"]);
    }

    public function testWritesCorrectDataTypes()
    {
        $tmpFile = new PhoreTempFile();
        $w = new TCLDataWriter($fs = $tmpFile->fopen("w"));

        $w->inject(1, "sig1:rpm", "aßäöü");
        $this->assertEquals(1, $w->getStats()["strings"]);

        $w->inject(1, "sig1:rpm", "1.0000000");
        $this->assertEquals(1, $w->getStats()["basic_data"]);

        $w->inject(1, "sig1:rpm", 1.1);
        $this->assertEquals(1, $w->getStats()["numeric"]);

        $w->inject(1, "sig1:rpm", -1.01);
        $this->assertEquals(2, $w->getStats()["numeric"]);

        $w->close();

        // Read the data and compare with input-Data

        $r = new TCLDataReader();
        $out = [];
        $r->setOnDataCb(function($timestamp, $signalName, $value, $measureUnit) use (&$out) {
            $out[] = [$timestamp, $signalName, $value, $measureUnit];
        });

        $fs = $tmpFile->fopen("r");
        $r->parse($fs);

        $this->assertEquals([1, "sig1", "aßäöü", "rpm"], $out[0]);
        $this->assertEquals([1, "sig1", 1, "rpm"], $out[1]);
        $this->assertEquals([1, "sig1", 1.1, "rpm"], $out[2]);
        $this->assertEquals([1, "sig1", -1.01, "rpm"], $out[3]);
    }
}
