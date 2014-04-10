<?php
/**
 * Last Change: 2014 Apr 05, 20:57
 */

namespace eq\assets\eq;

class DebugAsset extends \eq\web\AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/eq";
    protected $base_path = "@www/assets/eq";
    protected $base_url = "@web/assets/eq";

    protected $css = [
        "eq.debug-0.0.1.css"
    ];

    protected $js = [
        "eq.debug-0.0.1.js",
    ];

}
