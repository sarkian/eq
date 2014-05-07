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
        }
        else {
            foreach(array_reverse($trace) as $call) {
                if(isset($call['file'])) {
                    $file = $call['file'];
                    $line = $call['line'];
                    break;
                }
            }
        }
        if(!$absolute)
            $file = self::relativePath($file);
        return [$file, $line];
    }

    public static function relativePath($path, $root = null)
    {
        if(is_null($root))
            $root = EQ::app()->config("system.project_root", "@app");
        return preg_replace("/^".preg_quote($root).'\//', "", $path);
    }

} 