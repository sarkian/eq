<?php
/**
 * Last Change: 2014 Apr 20, 00:43
 */

namespace eq\modules\navigation;

use EQ;

class NavigationModule extends \eq\base\ModuleBase
{

    public function __construct($config = [])
    {
        EQ::app()->registerComponent("navigation", $this);
    }

}
