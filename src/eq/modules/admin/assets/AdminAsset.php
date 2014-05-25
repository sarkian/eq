<?php

namespace eq\modules\admin\assets;

use eq\web\AssetBundle;

class AdminAsset extends AssetBundle
{

    protected static $_js = [];
    protected static $_css = [];

    public static function addJs($name)
    {
        if(!preg_match('/\.js$/', $name))
            $name .= ".js";
        if(!in_array($name, self::$_js))
            self::$_js[] = $name;
    }

    public static function addCss($name)
    {
        if(!preg_match('/\.css$/', $name))
            $name .= ".css";
        if(!in_array($name, self::$_css))
            self::$_css[] = $name;
    }

    public function getSourcePath()
    {
        return "@modules.eq:admin/assets/scripts";
    }

    public function getBasePath()
    {
        return "@www/assets/admin";
    }

    public function getBaseUrl()
    {
        return "@web/assets/admin";
    }

    public function getDepends()
    {
        return [
            "jquery",
            "eq.userdata",
//            "eq.ajax",
            "jquery.cookie",
        ];
    }

    public function getJs()
    {
        return array_merge([
            "admin.js",
        ], self::$_js);
    }

    public function getCss()
    {
        return array_merge([
            "admin.css",
        ], self::$_css);
    }

} 