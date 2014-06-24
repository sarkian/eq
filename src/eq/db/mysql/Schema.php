<?php

namespace eq\db\mysql;

class Schema extends \eq\db\Schema
{

    protected $type_map = [
        self::TYPE_PK => "INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY",
        self::TYPE_BIGPK => "BIGINT(20) NOT NULL AUTO_INCREMENT PRIMARY KEY",
        self::TYPE_TINYSTRING => "VARCHAR(255)",
        self::TYPE_SMALLSTRING => "VARCHAR(1024)",
        self::TYPE_STRING => "VARCHAR(2048)",
        self::TYPE_LONGSTRING => "VARCHAR(4069)",
        self::TYPE_TEXT => "TEXT",
        self::TYPE_MEDIUMTEXT => "MEDIUMTEXT",
        self::TYPE_LONGTEXT => "LONGTEXT",
        self::TYPE_SMALLINT => "SMALLINT(6)",
        self::TYPE_INT => "INT(11)",
        self::TYPE_BIGINT => "BIGINT(20)",
        self::TYPE_FLOAT => "FLOAT",
        self::TYPE_DECIMAL => "DECIMAL(10,0)",
        self::TYPE_DATETIME => "DATETIME",
        self::TYPE_TIMESTAMP => "TIMESTAMP",
        self::TYPE_TIME => "TIME",
        self::TYPE_DATE => "DATE",
        self::TYPE_BINARY => "BLOB",
        self::TYPE_BOOL => "TINYINT(1)",
        self::TYPE_MONEY => "DECIMAL(19,4)",
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
