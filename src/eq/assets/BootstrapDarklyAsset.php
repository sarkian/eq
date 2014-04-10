<?php
/**
 * Last Change: 2014 Apr 09, 15:24
 */

namespace eq\assets;

class BootstrapDarklyAsset extends \eq\web\AssetBundle
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

