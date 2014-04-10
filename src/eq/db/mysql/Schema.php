<?php
/**
 * Last Change: 2014 Mar 15, 16:38
 */

namespace eq\db\mysql;

class Schema extends \eq\db\Schema
{

    public function quoteSimpleTableName($name)
    {
        return strpos($name, "`") !== false ? $name : "`".$name."`";
    }

    public function quoteSimpleColumnName($name)
    {
        return strpos($name, "`") !== false || $name === "*"
            ? $name : "`".$name."`";
    }

}
