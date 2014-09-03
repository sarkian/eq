<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class TooltipsterAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery/tooltipster";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $_depends = [
        "jquery",
    ];

    protected $js = [
        "jquery.tooltipster.min.js",
    ];

    protected $_css = [
        "tooltipster.css",
    ];

    protected static $theme = null;

    public function getCss()
    {
        return static::$theme
            ? array_merge($this->_css, ["themes/tooltipster-".static::$theme.".css"])
            : $this->_css;
    }

    public static function registerWithTheme($theme, $reload = EQ_ASSETS_DBG)
    {
        static::$theme = $theme;
        static::register($reload);
    }

} 