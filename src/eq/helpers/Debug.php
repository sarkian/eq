<?php

namespace eq\helpers;


use EQ;

class Debug
{

    public static function callLocation($skip = 1, $absolute = false)
    {
        $trace = debug_backtrace();
        $file = "UNKNOWN";
        $line = 0;
        if(isset($trace[$skip]['file'])) {
            $file = $trace[$skip]['file'];
            $line = $trace[$skip]['line'];
        } else {
            foreach(array_reverse($trace) as $call) {
                if(isset($call['file'])) {
                    $file = $call['file'];
                    $line = $call['line'];
                    break;
                }
            }
        }
        if(!$absolute)
            $file = EQ::unalias($file);
        return [$file, $line];
    }

}