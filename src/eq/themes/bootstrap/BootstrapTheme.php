<?php
/**
 * Last Change: 2014 Apr 30, 20:38
 */

namespace eq\themes\bootstrap;

class BootstrapTheme extends \eq\web\ThemeBase
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
