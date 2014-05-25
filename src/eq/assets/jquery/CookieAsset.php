<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class CookieAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $js = [
        "jquery.cookie.js",
    ];

    protected $depends = [
        "jquery",
    ];

} 