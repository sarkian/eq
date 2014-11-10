<?php

namespace eq\helpers;

class Path
{

    /**
     * @param array|string $parts, ...
     * @return string
     */
    public static function join($parts)
    {
        $parts = [];
        foreach(func_get_args() as $arg) {
            if(is_array($arg))
                $parts = array_merge($parts, $arg);
            else
                $parts[] = $arg;
        }
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

}
