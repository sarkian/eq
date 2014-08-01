<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class SelectizeAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery/selectize";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $_depends = [
        "jquery",
    ];

    protected $js = [
        "selectize.min.js",
    ];

    protected $_css = [
        "selectize.css",
    ];

    protected static $theme = "default";

    protected function getCss()
    {
        return array_merge($this->_css, ["selectize.".static::$theme.".css"]);
    }

    public static function registerWithTheme($theme, $reload = EQ_ASSETS_DBG)
    {
        static::$theme = $theme;
        static::register($reload);
    }

} 