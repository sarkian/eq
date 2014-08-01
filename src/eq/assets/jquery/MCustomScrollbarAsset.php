<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class MCustomScrollbarAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery/mCustomScrollbar";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $depends = [
        "jquery",
        "jquery.mousewheel",
    ];

    protected $js = [
        "jquery.mCustomScrollbar.concat.min.js",
    ];

    protected $css = [
        "jquery.mCustomScrollbar.min.css",
    ];

} 