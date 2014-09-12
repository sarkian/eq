<?php

namespace eq\modules\admin\controllers;

use EQ;
use eq\web\Controller;

class AdminControllerBase extends Controller
{

    public function getTemplate()
    {
        return EQ::getAlias("@modules.eq:admin/templates/main.php");
    }

    protected function permissions()
    {
        return [
            'user,guest' => ["deny", "#all"],
        ];
    }

} 