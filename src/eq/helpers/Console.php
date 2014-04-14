<?php
/**
 * Last Change: 2014 Apr 14, 15:43
 */

namespace eq\helpers;

class Console
{

    public static function stdin($raw = false)
    {
        return $raw ? fgets(STDIN) : trim(fgets(STDIN), " \r\n\t");
    }

    public static function stdout($str)
    {
        return fwrite(STDOUT, $str."\n");
    }

    public static function stderr($str)
    {
        return fwrite(STDERR, $str."\n");
    }

}
