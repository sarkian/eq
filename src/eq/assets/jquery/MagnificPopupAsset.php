<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class MagnificPopupAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery/mfp";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $depends = [
        "jquery",
    ];

    protected $js = [
        "jquery.magnific-popup.min.js",
    ];

    protected $css = [
        "magnific-popup.css",
    ];

} 