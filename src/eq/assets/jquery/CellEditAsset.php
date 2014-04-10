<?php
/**
 * Last Change: 2014 Mar 18, 19:42
 */

namespace eq\assets\jquery;

class CellEditAsset extends \eq\web\AssetBundle
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
