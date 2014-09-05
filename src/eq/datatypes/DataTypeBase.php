<?php

namespace eq\datatypes;

use EQ;
use eq\base\InvalidCallException;
use eq\db\Schema;
use eq\helpers\Str;
use eq\base\Loader;

abstract class DataTypeBase
{

    public function __call($name, $args = [])
    {
        $cls = get_called_class();
        if(is_callable([$cls, $name]))
            return call_user_func_array([$cls, $name], $args);
        throw new InvalidCallException("Call to undefined method: $cls::$name");
    }

    /**
     * @param string $type
     * @return string|DataTypeBase
     * @throws DataTypeException
     */
    public static final function getClass($type)
    {
        if(Loader::classExists($type)
            && isset(class_parents($type)[get_called_class()])
        )
            return $type;
        $cbasename = Str::var2method($type);
        $cname = EQ::app()->app_namespace.'\datatypes\\'.$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        $cname = 'eq\datatypes\\'.$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        else
            throw new DataTypeException("Data type class not found: $cname");
    }

    public static final function typename()
    {
        return Str::method2var(Str::classBasename(get_called_class()));
    }

    public static final function getTypeForValue($val)
    {
        $types = [
            'boolean' => "bool",
            'integer' => "int",
            'double' => "float",
            'string' => "str",
            'array' => "arr",
        ];
        $type = gettype($val);
        return isset($types[$type]) ? $types[$type] : "str";
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
        return !(bool) strlen((string) $value);
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
        return static::cast($value);
    }

    public static function toDb($value)
    {
        return static::cast($value);
    }

    public static function cast($value)
    {
        return $value;
    }

    public static function isA($value)
    {
        return true;
    }

    public static function sqlType()
    {
        return Schema::TYPE_TINYSTRING;
    }

    public static function formControl()
    {
        return "textField";
    }

    public static function formControlOptions()
    {
        return [];
    }

    public static function defaultValue()
    {
        return null;
    }

}
