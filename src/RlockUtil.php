<?php

namespace Sdyyf\Rlock;

class RlockUtil
{
    public static function currentMicrotime() :float
    {
        return round(microtime(true), 3);
    }
    
    public static function futureMicrotime(int $afterSeconds) :float
    {
        return round(self::currentMicrotime() + $afterSeconds, 3);
    }
    
}
