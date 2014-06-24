<?php

namespace eq\datatypes;

use eq\db\Schema;

class UserSessions extends DataTypeBase
{

    public static function validate($value)
    {
        if(!is_array($value))
            return false;
        foreach($value as $session) {
            if(!preg_match("/^[a-zA-Z0-9]+$/", $session))
                return false;
        }
        return true;
    }

    public static function toDb($value)
    {
        return json_encode($value);
    }

    public static function fromDb($value)
    {
        $value = json_decode($value);
        is_array($value) or $value = [];
        return $value;
    }

    public static function sqlType()
    {
        return Schema::TYPE_TEXT;
    }

} 