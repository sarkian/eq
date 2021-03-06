<?php

namespace eq\assets\jquery;

use eq\web\AssetBundle;

class CellEditAsset extends AssetBundle
{

    protected $source_path = "@eq/src/eq/assets/scripts/jquery";
    protected $base_path = "@www/assets";
    protected $base_url = "@web/assets";

    protected $js = [
        "jquery.cell-edit-0.0.1.js",
    ];

    protected $depends = [
        "jquery",
    ];

}
