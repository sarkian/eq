<?php

namespace eq\base;

class UnknownPropertyException extends ExceptionBase
{

    /**
     * @param object|string $cls_or_msg
     * @param string $pname
     * @param bool $set
     */
    public function __construct($cls_or_msg, $pname = null, $set = false)
    {
        if(func_num_args() < 2) {
            $msg = $cls_or_msg;
        }
        else {
            $cname = is_object($cls_or_msg) ? get_class($cls_or_msg) : $cls_or_msg;
            $msg = ($set ? "Setting" : "Getting")." unknown property: $cname::\$$pname";
        }
        parent::__construct($msg);
    }

}
