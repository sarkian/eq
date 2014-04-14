<?php

namespace eq\console;

use eq\helpers\Str;

class Command
{

    private static $_instances = [];
    private static $_reflections = [];

    private final function __construct()
    {
        $this->init();
    }

    public static final function className()
    {
        return get_called_class();
    }

    public static final function commandName()
    {
        $cname = Str::classBasename(get_called_class());
        return Str::method2cmd(preg_replace("/Command$/", "", $cname));
    }

    public static final function inst()
    {
        $cname = get_called_class();
        if(!isset(self::$_instances[$cname]))
            self::$_instances[$cname] = new $cname();
        return self::$_instances[$cname];
    }

    public static final function reflect()
    {
        $cname = get_called_class();
        if(!isset(self::$_reflections[$cname]))
            self::$_reflections[$cname] = new ReflectionCommand($cname);
        return self::$_reflections[$cname];
    }

    protected function init()
    {
        
    }

}
