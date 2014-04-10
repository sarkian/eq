<?php
/**
 * Last Change: 2014 Apr 08, 01:20
 */

namespace eq\modules\clog;

use EQ;

class ClogController extends \eq\web\Controller
{

    public function actionProcess($key)
    {
        $file = EQ::getAlias("@runtime/clog/$key");
        echo file_get_contents($file);
        unlink($file);
    }

}
