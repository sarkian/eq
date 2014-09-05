<?php

namespace eq\base;

abstract class StaticComponent
{

    private static $_instances = [];

    /**
     * @param array $config
     * @return static|StaticComponent
     */
    public static function inst($config = [])
    {
        $cls = get_called_class();
        if(!isset(self::$_instances[$cls]))
            self::$_instances[$cls] = new $cls($config);
        return self::$_instances[$cls];
    }

} 