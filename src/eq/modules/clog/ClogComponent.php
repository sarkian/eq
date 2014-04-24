<?php
/**
 * Last Change: 2014 Apr 24, 04:53
 */

namespace eq\modules\clog;

use EQ;

class ClogComponent
{

    public function log()
    {
        EQ::app()->module("clog")->addMsg("log", func_get_args());
    }

    public function warn()
    {
        EQ::app()->module("clog")->addMsg("warn", func_get_args());
    }

    public function err()
    {
        EQ::app()->module("clog")->addMsg("err", func_get_args());
    }

}
