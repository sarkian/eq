<?php
/**
 * Last Change: 2014 Mar 20, 18:10
 */

namespace eq\assets;

class BootstrapFontsAsset extends \eq\web\AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/bootstrap";
    protected $base_path = "@www/assets/bootstrap";
    protected $base_url = "@web/assets/bootstrap";

    protected $files = [
        "fonts",
    ];

}
