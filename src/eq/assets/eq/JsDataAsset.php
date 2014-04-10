<?php
/**
 * Last Change: 2014 Apr 05, 14:18
 */

namespace eq\assets\eq;

class JsDataAsset extends \eq\web\AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/eq";
    protected $base_path = "@www/assets/eq";
    protected $base_url = "@web/assets/eq";

    protected $js = [
        "eq.js-data-0.0.1.js",
    ];

    protected $depends = [
        "eq.base",
    ];

}
