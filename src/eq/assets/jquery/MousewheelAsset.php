<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class MousewheelAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $depends = [
        "jquery",
    ];

    protected $js = [
        "jquery.mousewheel.min.js",
    ];

}