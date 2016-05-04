<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class TurbolinksAsset extends AssetBundle {

    protected $source_path = "@eq/src/eq/assets/scripts/jquery";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $js = [
        "jquery.turbolinks.min.js",
    ];

    protected $depends = [
        "jquery",
        "turbolinks",
    ];

}