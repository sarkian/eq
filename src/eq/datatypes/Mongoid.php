<?php

namespace eq\datatypes;

class Mongoid extends Str
{

    public static function validate($value)
    {
        if(is_object($value) && $value instanceof \MongoId)
            return true;
        if(is_string($value) && preg_match('/^[a-z0-9]{24,24}$/', $value))
            return true;
        return false;
    }

    public static function filter($value)
    {
        return preg_replace('[^a-z0-9]', '', $value);
    }

    public static function toDb($value)
    {
        return is_object($value) && $value instanceof \MongoId ? $value : new \MongoId($value);
    }

}