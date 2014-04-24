<?php
/**
 * Last Change: 2014 Apr 24, 04:49
 */

namespace eq\modules\clog\controllers;

use EQ;
use eq\web\HttpException;

class ClogController extends \eq\web\Controller
{

    public function actionProcess($key)
    {
        $file = EQ::getAlias("@runtime/clog/$key");
        if(!file_exists($file))
            throw new HttpException(404);
        echo file_get_contents($file);
        unlink($file);
    }

}
