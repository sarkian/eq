<?php
/**
 * Last Change: 2014 Feb 18, 19:38
 */

namespace eq\helpers;

class Dbg
{

    public static function dump($val)
    {
        ob_start();
        var_dump($val);
        return ob_get_clean();
    }

}
