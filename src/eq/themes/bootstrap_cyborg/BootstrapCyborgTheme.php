<?php

namespace eq\themes\bootstrap_cyborg;

use eq\themes\bootstrap\BootstrapTheme;

class BootstrapCyborgTheme extends BootstrapTheme
{

    public function getAssets()
    {
        return [
            "jquery",
            "normalize-css",
//            "bootstrap-base",
            "bootstrap-theme-cyborg",
            "bootstrap-js",
//            "eq.base",
        ];
    }

} 