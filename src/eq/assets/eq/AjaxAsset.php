<?php

namespace eq\assets\eq;

use eq\web\AssetBundle;

class AjaxAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/eq";
    protected $base_path = "@www/assets/eq";
    protected $base_url = "@web/assets/eq";

    protected $js = [
        "eq.ajax-0.0.1.js",
    ];

    protected $depends = [
        "eq.base",
        "jquery",
    ];

} 