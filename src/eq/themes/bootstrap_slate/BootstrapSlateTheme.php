<?php

namespace eq\themes\bootstrap_slate;

use eq\themes\bootstrap\BootstrapTheme;

class BootstrapSlateTheme extends BootstrapTheme
{

    public function getAssets()
    {
        return [
            "jquery",
            "normalize-css",
//            "bootstrap-base",
            "bootstrap-theme-slate",
            "bootstrap-js",
//            "eq.base",
        ];
    }

} 