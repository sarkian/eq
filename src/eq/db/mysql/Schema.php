<?php

namespace eq\db\mysql;

class Schema extends \eq\db\Schema
{

    protected $type_map = [

    ];

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
