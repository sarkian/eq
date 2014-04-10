<?php
/**
 * Last Change: 2014 Mar 18, 19:29
 */

namespace eq\assets;

class BootstrapJsAsset extends \eq\web\AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/bootstrap";
    protected $base_path = "@www/assets/bootstrap";
    protected $base_url = "@web/assets/bootstrap";

    protected $js = [
        "js/bootstrap.min.js",
    ];

    protected $depends = [
        "jquery",
    ];

}
