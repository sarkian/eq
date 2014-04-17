<?php
/**
 * Last Change: 2014 Apr 17, 22:26
 */

namespace eq\modules\user\controllers;

use EQ;
use eq\modules\user\UserModule;
use eq\modules\user\models\Users;
use eq\web\HttpException;

class UserController extends \eq\web\ModuleController
{

    protected function permissions()
    {
        return [
            'guest' => ["deny", "logout"],
        ];
    }

    public function getTemplate()
    {
        return "main";
    }

    public function actionLogin()
    {
        $this->createTitle(EQ::t("Login"));
        $model = new Users("login");
        $widget = $this->config("login_form_widget", "eq.BootstrapModelForm");
        $this->render("login", [
            'model' => $model,
            'widget' => $widget,
        ]);
    }

    public function actionLogout()
    {
        
    }

    public function actionRegister()
    {
        
    }

    public function actionTest()
    {
        header("Content-type: text/plain");
        var_dump($this->module_class);
        var_dump($this->module_namespace);
        var_dump($this->module_name);
    }

}
