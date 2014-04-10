<?php
/**
 * Last Change: 2014 Apr 08, 23:56
 */

namespace eq\base;

use EQ;
use eq\helpers\Str;

abstract class ModuleBase extends ModuleAbstract
{

    use TObject;

    private static $_instances = [];

    public static final function init($config)
    {
        $cname = get_called_class();
        if(isset(self::$_instances[$cname]))
            return;
        $inst = new $cname($config);
        self::$_instances[$cname] = $inst;
    }

    public static final function enabled($cname = null)
    {
        $cname or $cname = get_called_class();
        return isset(self::$_instances[$cname]);
    }

    public static final function getClass($name)
    {
        $path = explode(".", $name);
        array_push($path, 
            Str::cmd2var($name)."\\".Str::cmd2method(
                array_pop($path))."Module");
        $cbasename = implode("\\", $path);
        $cname = EQ::app()->app_namespace."\\modules\\".$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        $cname = 'eq\modules\\'.$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        throw new ModuleException("Module class not found: $name");
    }


    protected $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getDepends()
    {
        return [];
    }

    protected function registerComponent($name, $class,
        $config = [], $preload = false)
    {
        EQ::app()->registerComponent($name, $class, $config, $preload);
    }

    protected function registerStaticMethod($name, $method)
    {
        EQ::app()->registerStaticMethod($name, $method);
    }

}
