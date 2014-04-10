<?php
/**
 * Last Change: 2013 Dec 27, 20:37
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
        return fwrite(STDOUT, $str);
    }

    public static function stderr($str)
    {
        return fwrite(STDERR, $str);
    }

}
