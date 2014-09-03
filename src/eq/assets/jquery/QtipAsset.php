<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class QtipAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery/qtip";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $_depends = [
        "jquery",
    ];

    protected $js = [
        "jquery.qtip.min.js",
    ];

    protected $css = [
        "jquery.qtip.min.css",
    ];

} 