<?php
/**
 * Last Change: 2014 Apr 24, 05:00
 */

namespace eq\modules\i18n;

use EQ;

class I18nComponent
{

    public function addDir($dir, $key_prefix = "")
    {
        EQ::app()->module("i18n")->addDir($dir, $key_prefix);
    }

}
