<?php

namespace eq\web;

use EQ;
use eq\base\Loader;
use eq\base\TObject;
use eq\helpers\Str;

/**
 * @property array assets
 */
abstract class ThemeBase
{

    use TObject;

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    public final function registerAssets()
    {
        foreach($this->assets as $aname) {
            EQ::app()->client_script->addBundle($aname);
        }
    }

    /**
     * @param string $name
     * @return string|bool
     */
    public final function widgetClass($name)
    {
        $classes = array_values(class_parents($this));
        array_unshift($classes, get_called_class());
        foreach($classes as $class) {
            $cname = Str::classNamespace($class).'\widgets\\'.$name;
            if(Loader::classExists($cname))
                return $cname;
        }
        return false;
    }

    public function getAssets()
    {
        
    }

}
