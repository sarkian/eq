<?php

namespace eq\themes\bootstrap_traditional;

use eq\themes\bootstrap\BootstrapTheme;

class BootstrapTraditionalTheme extends BootstrapTheme
{

    public function getAssets()
    {
        return [
            "jquery",
            "normalize-css",
            "bootstrap-base",
            "bootstrap-theme",
            "bootstrap-js",
        ];
    }

} 