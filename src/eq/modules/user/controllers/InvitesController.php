<?php

namespace eq\modules\user\controllers;

use eq\base\TModuleClass;
use eq\web\Controller;
use EQ;

class InvitesController extends Controller
{

    use TModuleClass;

    public function getTemplate()
    {
        return EQ::getAlias("@modules.eq:admin/templates/main.php");
    }

    public function actionIndex()
    {
        $this->render("invites/index");
    }

} 