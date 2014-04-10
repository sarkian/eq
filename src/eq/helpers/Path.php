<?php
/**
 * Last Change: 2013 Dec 26, 18:15
 */

namespace eq\helpers;

class Path
{

    public static function join($parts)
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

}
