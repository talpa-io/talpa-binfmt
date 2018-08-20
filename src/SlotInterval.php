<?php
/**
 * Created by PhpStorm.
 * User: matthias
 * Date: 20.08.18
 * Time: 12:31
 */

namespace Talpa\BinFmt;


class SlotInterval
{

    const RAW_DATA_INTERVAL = 3600;             // One hour
    const SHIFT_INDEX_INTERVAL = 604800 * 4;    // 4 WEEKS in seconds

    public function getRawDataSlotId(int $timestamp) : int
    {
        return (((int)($timestamp / self::RAW_DATA_INTERVAL)) * self::RAW_DATA_INTERVAL);
    }

    public function getShiftSlotId(int $timestamp) : int
    {
        return (((int)($timestamp / self::SHIFT_INDEX_INTERVAL)) * self::SHIFT_INDEX_INTERVAL);
    }
}
