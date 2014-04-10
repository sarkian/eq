<?php
/**
 * Last Change: 2014 Apr 08, 01:20
 */

namespace eq\modules\clog;

use EQ;

class Clog
{

    public static function log()
    {
        EQ::app()->clog->addMsg("log", func_get_args());
    }

    public static function warn()
    {
        EQ::app()->clog->addMsg("warn", func_get_args());
    }

    public static function err()
    {
        EQ::app()->clog->addMsg("err", func_get_args());
    }


}
