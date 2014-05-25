<?php

namespace eq\assets;

use eq\web\AssetBundle;

class BootstrapDarklyAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/bootstrap";
    protected $base_path = "@www/assets/bootstrap";
    protected $base_url = "@web/assets/bootstrap";

    protected $depends = [
        "bootstrap-fonts",
    ];

    protected $css = [
        // "css/bootstrap-darkly.min.css",
        "css/bootstrap-darkly.css",
    ];

}

