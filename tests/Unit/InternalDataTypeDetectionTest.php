<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.10.18
 * Time: 15:24
 */

namespace Talpa\BinFmt\Test\Unit;


use Phore\FileSystem\PhoreTempFile;
use PHPUnit\Framework\TestCase;
use Talpa\BinFmt\V1\TBinFmt;
use Talpa\BinFmt\V1\TCLDataReader;
use Talpa\BinFmt\V1\TCLDataWriter;

class InternalDataTypeDetectionTest extends TestCase
{


    private $lastDataType;

    private function _testInputOutputDataTypesEqual($input)
    {
        $tmpFile = new PhoreTempFile();
        $w = new TCLDataWriter($fs = $tmpFile->fopen("w"));
        $w->inject(1, "col1", $input, "mu");
        $this->lastDataType = $w->getLastDataType();
        $w->close();

        // Read the data and compare with input-Data

        $r = new TCLDataReader();
        $out = [];
        $r->setOnDataCb(function($timestamp, $signalName, $value, $measureUnit) use (&$out) {
            $out[] = [$timestamp, $signalName, $value, $measureUnit];
        });

        $fs = $tmpFile->fopen("r");
        $r->parse($fs);

        return $out[0][2];
    }

    public function testStringData ()
    {
        $this->assertEquals("ABC", $this->_testInputOutputDataTypesEqual("ABC"));
        $this->assertEquals("äöüß+*~[];", $this->_testInputOutputDataTypesEqual("äöüß+*~[];"));

    }

    public function testSpecialDataFormats()
    {
        $this->assertEquals(true, $this->_testInputOutputDataTypesEqual(true));
        $this->assertEquals(TBinFmt::TYPE_TRUE, $this->lastDataType);

        $this->assertEquals(false, $this->_testInputOutputDataTypesEqual(false));
        $this->assertEquals(TBinFmt::TYPE_FALSE, $this->lastDataType);

        $this->assertEquals(null, $this->_testInputOutputDataTypesEqual(null));
        $this->assertEquals(TBinFmt::TYPE_NULL, $this->lastDataType);
    }

    public function testBasicDataTypes ()
    {
        $this->assertEquals(5, $this->_testInputOutputDataTypesEqual(5));
        $this->assertEquals(5, $this->lastDataType);

        $this->assertEquals(-1, $this->_testInputOutputDataTypesEqual(-1));
        $this->assertEquals(TBinFmt::TYPE_INT8_NEG, $this->lastDataType);

        $this->assertEquals(199, $this->_testInputOutputDataTypesEqual(199));
        $this->assertEquals(199, $this->lastDataType);
        $this->assertEquals(-200, $this->_testInputOutputDataTypesEqual(-200));
        $this->assertEquals(TBinFmt::TYPE_INT8_NEG, $this->lastDataType);

        $this->assertEquals(1.1, $this->_testInputOutputDataTypesEqual(1.1));
        $this->assertEquals(TBinFmt::TYPE_MIN1_FLOAT, $this->lastDataType);
        $this->assertEquals(-1.1, $this->_testInputOutputDataTypesEqual(-1.1));
        $this->assertEquals(TBinFmt::TYPE_MIN1_FLOAT, $this->lastDataType);
    }


    public function testFloatShortTypes()
    {
        $this->assertEquals(1.1, $this->_testInputOutputDataTypesEqual(1.1));
        $this->assertEquals(TBinFmt::TYPE_MIN1_FLOAT, $this->lastDataType);
        $this->assertEquals(-1.1, $this->_testInputOutputDataTypesEqual(-1.1));
        $this->assertEquals(TBinFmt::TYPE_MIN1_FLOAT, $this->lastDataType);

        $this->assertEquals(1.123, $this->_testInputOutputDataTypesEqual(1.123));
        $this->assertEquals(TBinFmt::TYPE_MIN3_FLOAT, $this->lastDataType);
        $this->assertEquals(-1.123, $this->_testInputOutputDataTypesEqual(-1.123));
        $this->assertEquals(TBinFmt::TYPE_MIN3_FLOAT, $this->lastDataType);

        $this->assertEquals(31.12, $this->_testInputOutputDataTypesEqual(31.12));
        $this->assertEquals(TBinFmt::TYPE_MIN2_FLOAT, $this->lastDataType);
        $this->assertEquals(-31.12, $this->_testInputOutputDataTypesEqual(-31.12));
        $this->assertEquals(TBinFmt::TYPE_MIN2_FLOAT, $this->lastDataType);

        $this->assertEquals(310.12, $this->_testInputOutputDataTypesEqual(310.12));
        $this->assertEquals(TBinFmt::TYPE_MIN2_FLOAT, $this->lastDataType);
        $this->assertEquals(-310.12, $this->_testInputOutputDataTypesEqual(-310.12));
        $this->assertEquals(TBinFmt::TYPE_MIN2_FLOAT, $this->lastDataType);

        $this->assertEquals(3100.1, $this->_testInputOutputDataTypesEqual(3100.1));
        $this->assertEquals(TBinFmt::TYPE_MIN1_FLOAT, $this->lastDataType);
        $this->assertEquals(-3100.1, $this->_testInputOutputDataTypesEqual(-3100.1));
        $this->assertEquals(TBinFmt::TYPE_MIN1_FLOAT, $this->lastDataType);

        $this->assertEquals(32000.1234, $this->_testInputOutputDataTypesEqual(32000.1234));
        $this->assertEquals(TBinFmt::TYPE_MED_FLOAT, $this->lastDataType);
        $this->assertEquals(-32000.1234, $this->_testInputOutputDataTypesEqual(-32000.1234));
        $this->assertEquals(TBinFmt::TYPE_MED_FLOAT, $this->lastDataType);

        $this->assertEquals(3200000.1234, $this->_testInputOutputDataTypesEqual(3200000.1234));
        $this->assertEquals(TBinFmt::TYPE_DOUBLE, $this->lastDataType);
        $this->assertEquals(-3200000.1234, $this->_testInputOutputDataTypesEqual(-3200000.1234));
        $this->assertEquals(TBinFmt::TYPE_DOUBLE, $this->lastDataType);

        $this->assertEquals(123.45678, $this->_testInputOutputDataTypesEqual(123.45678));
        $this->assertEquals(TBinFmt::TYPE_FLOAT, $this->lastDataType);
        $this->assertEquals(-123.45678, $this->_testInputOutputDataTypesEqual(-123.45678));
        $this->assertEquals(TBinFmt::TYPE_FLOAT, $this->lastDataType);

        $this->assertEquals(3200000.123456789, $this->_testInputOutputDataTypesEqual(3200000.123456789));
        $this->assertEquals(TBinFmt::TYPE_DOUBLE, $this->lastDataType);
        $this->assertEquals(-3200000.123456789, $this->_testInputOutputDataTypesEqual(-3200000.123456789));
        $this->assertEquals(TBinFmt::TYPE_DOUBLE, $this->lastDataType);
    }


}
