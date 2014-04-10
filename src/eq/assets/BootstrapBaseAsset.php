<?php
/**
 * Last Change: 2014 Mar 20, 18:04
 */

namespace eq\assets;

class BootstrapBaseAsset extends \eq\web\AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/bootstrap";
    protected $base_path = "@www/assets/bootstrap";
    protected $base_url = "@web/assets/bootstrap";

    protected $css = [
        "css/bootstrap.min.css",
    ];
    protected $files = [
        "fonts",
    ];

}
