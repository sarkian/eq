<?php

namespace eq\base;

abstract class ModuleAbstract
{

    protected static function instance()
    {
        
    }

    protected static function preInit()
    {

    }

    protected function registerComponent($name, $class, $config = null, $preload = false)
    {
        
    }

    protected function registerStaticMethod($name, $method)
    {
        
    }

    protected function configPermissions()
    {
        return [];
    }

    protected function ready()
    {
        
    }

    protected function addError($message)
    {

    }

    protected function addWarning($message)
    {

    }

}
