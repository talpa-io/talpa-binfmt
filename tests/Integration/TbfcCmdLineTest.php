<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 29.10.18
 * Time: 12:23
 */

namespace Talpa\BinFmt\Test\Integration;


use PHPUnit\Framework\TestCase;

class TbfcCmdLineTest extends TestCase
{

    private $MOCK_4COL_BASIC_DATA = __DIR__ . "/mock/in_csv_4col.csv";
    private $MOCK_5COL_BASIC_DATA = __DIR__ . "/mock/in_csv_5col.csv";

    public function testTbfcDefaultPack()
    {
        phore_exec("/opt/bin/tbfc --tbfc --pack --failOnErr --input=$this->MOCK_4COL_BASIC_DATA --out=/tmp/out.tbfc");

        phore_exec("/opt/bin/tbfc --tbfc --unpack --input=/tmp/out.tbfc --out=/tmp/out_compare.csv");

        $this->assertFileEquals($this->MOCK_4COL_BASIC_DATA, "/tmp/out_compare.csv");
    }

    /**
     * Test the hotfix for Indurad 5 col output format generates the same
     * output like the 4 col input format. (4 col)
     *
     * @throws \Exception
     */
    public function testTbfcInduradHotFixPack()
    {
        phore_exec("/opt/bin/tbfc --tbfc --pack --indurad5colQuickfix --failOnErr --input=$this->MOCK_5COL_BASIC_DATA --out=/tmp/out.tbfc");

        phore_exec("/opt/bin/tbfc --tbfc --unpack --input=/tmp/out.tbfc --out=/tmp/out_compare.csv");

        $this->assertFileEquals($this->MOCK_4COL_BASIC_DATA, "/tmp/out_compare.csv");
    }


    public function testStdInStdOutStreaming()
    {
        phore_exec("cat $this->MOCK_4COL_BASIC_DATA | bin/tbfc --tbfc --pack --failOnErr --stdin --stdout | bin/tbfc --tbfc --unpack --stdin --stdout > /tmp/out_compare2.csv");
        $this->assertFileEquals($this->MOCK_4COL_BASIC_DATA, "/tmp/out_compare2.csv");
    }


}
