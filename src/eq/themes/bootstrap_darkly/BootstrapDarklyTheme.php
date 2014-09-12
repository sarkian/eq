<?php

namespace eq\themes\bootstrap_darkly;

use EQ;
use eq\themes\bootstrap\BootstrapTheme;

class BootstrapDarklyTheme extends BootstrapTheme
{

    public function __construct()
    {

    }

    public function getAssets()
    {
        return [
            "jquery",
            "normalize-css",
//            "bootstrap-base",
            "bootstrap-theme-darkly",
            "bootstrap-js",
//            "eq.base",
        ];
    }

} 