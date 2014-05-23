<?php

namespace eq\web;

use EQ;
use eq\base\Object;
use eq\helpers\Str;
use eq\helpers\FileSystem;
use eq\base\Loader;

class AssetBundle extends Object
{

    // protected $source_path = "@eq/src/eq/assets/scripts";
    // protected $base_path = "@www/assets";
    // protected $base_url = "@web/assets";
    // protected $depends = [];
    // protected $js = [];
    // protected $css = [];
    // protected $files = [];
    // protected $before = [];

    public function __construct()
    {

    }

    protected function getSourcePath()
    {
        return "@eq/src/eq/assets/scripts";
    }

    protected function getBasePath()
    {
        return "@www/assets";
    }

    protected function getBaseUrl()
    {
        return "@web/assets";
    }

    protected function getDepends()
    {
        return [];
    }

    protected function getJs()
    {
        return [];
    }

    protected function getCss()
    {
        return [];
    }

    protected function getFiles()
    {
        return [];
    }

    protected function getAfter()
    {
        return [];
    }

    public static function getClass($name)
    {
        $path = explode(".", $name);
        array_push($path, Str::cmd2method(array_pop($path))."Asset");
        $cbasename = implode("\\", $path);
        $cname = EQ::app()->app_namespace.'\assets\\'.$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        if(count($path) > 1) {
            $cname = $path[0].'\assets\\'.$cbasename;
            if(Loader::classExists($cname))
                return $cname;
        }
        $cname = 'eq\assets\\'.$cbasename;
        if(Loader::classExists($cname))
            return $cname;
        throw new AssetException("Bundle class not found: $name");
    }

    public static function register($reload = EQ_ASSETS_DBG)
    {
        $class = get_called_class();
        EQ::app()->client_script->addBundle(new $class());
    }

    public function registerAssets($reload = EQ_ASSETS_DBG)
    {
        foreach($this->depends as $bundle)
            EQ::app()->client_script->addBundle($bundle, $reload);
        $css = [];
        $js = [];
        $files = $this->files;
        foreach($this->css as $file) {
            if(!in_array($file, $files))
                $files[] = $file;
            $css[] = $file;
        }
        foreach($this->js as $file) {
            $pos = ClientScript::POS_HEAD;
            if(is_array($file)) {
                $pos = $file[1];
                $file = $file[0];
            }
            if(!in_array($file, $files))
                $files[] = $file;
            $js[] = $file;
        }
        foreach($files as $file)
            FileSystem::copy($this->source_path."/$file",
                $this->base_path."/$file", $reload);
        foreach($css as $file)
            EQ::app()->client_script->addCssFile(
                EQ::getAlias($this->base_url."/$file")
            );
        foreach($js as $file)
            EQ::app()->client_script->addJsFile(
                EQ::getAlias($this->base_url."/$file"), $pos
            );
        foreach($this->after as $bundle)
            EQ::app()->client_script->addBundle($bundle, $reload);
    }

}
