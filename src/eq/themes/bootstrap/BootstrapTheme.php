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
            "bootstrap-darkly",
            "bootstrap-js",
            "eq.base",
        ];
    }

}
