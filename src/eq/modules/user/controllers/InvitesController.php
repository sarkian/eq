<?php

namespace eq\modules\user\controllers;

use eq\base\TModuleClass;
use eq\modules\admin\assets\AdminAsset;
use eq\web\Controller;
use EQ;

class InvitesController extends Controller
{

    use TModuleClass;

    protected function permissions()
    {
        return [
            'user,guest' => ["deny", "#all"],
        ];
    }

    protected function beforeRender()
    {
        AdminAsset::register();
    }

    public function getTemplate()
    {
        return EQ::getAlias("@modules.eq:admin/templates/main.php");
    }

    public function actionIndex()
    {
        $this->render("invites/index");
    }

} 