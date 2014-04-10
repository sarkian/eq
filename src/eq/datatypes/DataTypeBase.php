<?php

namespace eq\datatypes;

use EQ;
use eq\helpers\Str;
use eq\base\Loader;
use eq\web\html\HtmlNode;

abstract class DataTypeBase
{

    public static final function getClass($type)
    {
        $cbasename = Str::var2method($type);
        $cname = "\\".EQ::app()->app_namespace."\datatypes\\$cbasename";
        if(Loader::classExists($cname))
            return $cname;
        $cname = "\\eq\datatypes\\$cbasename";
        if(Loader::classExists($cname))
            return $cname;
        else
            throw new DataTypeException("Data type class not found: $cname");
    }

    public static final function c()
    {
        return get_called_class();
    }

    public static function registerConstant($constname)
    {
        defined($constname) or define($constname, get_called_class());
    }

    public static function isEmpty($value)
    {
        return !(bool) strlen( (string) $value );
    }

    public static function validate($value)
    {
        return true;
    }

    public static function pattern()
    {
        return ".+";
    }

    public static function filter($value)
    {
        return $value;
    }

    public static function fromDb($value)
    {
        return self::cast($value);
    }

    public static function toDb($value)
    {
        return self::cast($value);
    }

    public static function cast($value)
    {
        return $value;
    }

    public static function sqlType($engine = null)
    {
        return "VARCHAR(255)";
    }

    public static function formControl()
    {
        return "textField";
    }

    public static function formControlOptions()
    {
        return [];
    }

}
