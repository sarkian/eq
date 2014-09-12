<?php

namespace eq\themes\bootstrap;

use eq\web\ThemeBase;

class BootstrapTheme extends ThemeBase
{

    public function getAssets()
    {
        return [
            "jquery",
            "normalize-css",
            "bootstrap-base",
//            "bootstrap-theme",
            "bootstrap-js",
//            "eq.base",
        ];
    }

}
