<?php
/**
 * Last Change: 2014 Apr 09, 03:30
 */

namespace eq\base;

use EQ;
use eq\helpers\Str;

trait TAutobind
{

    public function autobind()
    {
        foreach(get_class_methods($this) as $method) {
            if(strncmp($method, "__on", 4))
                continue;
            $parts = array_diff(explode("_", substr($method, 4)), [""]);
            $event = lcfirst(array_pop($parts));
            $module = Str::method2var(implode("_", $parts));
            $eventname = $module ? $module.".".$event : $event;
            EQ::app()->bind($eventname, [$this, $method]);
        }
    }

}
