<?php

namespace eq\assets;

use eq\web\AssetBundle;

class PnotifyAsset extends AssetBundle
{

    protected $js = [
        "pnotify.min.js",
    ];

    protected $css = [
        "pnotify.min.css",
    ];

    protected $depends = [
        "jquery",
    ];

} 