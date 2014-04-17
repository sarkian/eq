<?php
/**
 * Last Change: 2014 Apr 17, 13:16
 */

namespace eq\base;

abstract class ModuleAbstract
{

    protected function registerComponent($name, $class, $config = [], $preload = false)
    {
        
    }

    protected function registerStaticMethod($name, $method)
    {
        
    }

    protected function registerCommand($command, $class)
    {
        
    }

    protected static function configPermissions()
    {
        return [];
    }

}
