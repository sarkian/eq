<?php

namespace eq\modules\clog\controllers;

use EQ;
use eq\web\Controller;
use eq\web\HttpException;

class ClogController extends Controller
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
