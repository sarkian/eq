<?php

namespace eq\modules\user\controllers;

use EQ;
use eq\base\TModuleClass;
use eq\data\ModelBase;
use eq\modules\user\models\TUser;
use eq\web\Controller;
use eq\web\HttpException;
use eq\widgets\ConfigForm;

class UserController extends Controller
{

    use TModuleClass;

    protected function permissions()
    {
        return [
            'guest' => ["deny", "logout,account"],
            'user,admin' => ["deny", "login,register", "/"],
        ];
    }

    public function getTemplate()
    {
        return "main";
    }

    public function actionLogin()
    {
        $this->createTitle(EQ::t("Login"));
        $model = EQ::app()->user->setScenario("login");
        $form = EQ::widget($this->config("login_form_widget"), $model);
        if(EQ::app()->request->isPost()) {
            $model->apply($form->getData(), true);
            if($model->isAuth())
                $this->redir($this->module->config("login_redirect_url"));
        }
        $this->render("login", [
            'model' => $model,
            'form' => $form,
        ]);
    }

    public function actionLogout()
    {
        if(EQ::app()->validateToken()) {
            EQ::app()->user->setScenario("logout");
            EQ::app()->destroyToken();
        }
        $this->redir($this->module->config("logout_redirect_url"));
    }

    public function actionRegister()
    {
        if(!$this->config("registration_enabled"))
            throw new HttpException(404);
        $this->createTitle(EQ::t("Register"));
        $model = EQ::app()->user->setScenario("register");
        $form = EQ::widget($this->config("register_form_widget"), $model);
        if(EQ::app()->request->isPost()) {
            $model->apply($form->getData(), true);
            if($model->isAuth())
                $this->redir($this->module->config("register_redirect_url"));
        }
        $this->render("register", [
            'model' => $model,
            'form' => $form,
        ]);
    }

    public function actionAccount()
    {
        if(!$this->config("account_page.enabled"))
            throw new HttpException(404);
        $this->createTitle(EQ::t("Account settings"));
        EQ::app()->client_script->addBundle("eq.ajax");
        $model = EQ::app()->user->setScenario("update");
        $form = $this->createAccountForm($model);
        if(EQ::app()->request->isPost()) {
            $data = $form->getData();
            $model->apply($data, true);
            if(isset($data['settings_theme']))
                $model->settingsSet("theme", $data['settings_theme']);
            $form = $this->createAccountForm($model);
            if(!$model->save()) {
                $form->errors = $model->errors;
                $form->errors_by_field = $model->errors_by_field;
            }
            else
                $this->redir("{modules.eq:user.user.account}");
        }
        $this->render("account", ['form' => $form]);
    }

    /**
     * @param ModelBase|TUser $model
     * @return ConfigForm
     */
    protected function createAccountForm(ModelBase $model)
    {
        $fields = [
            'info' => [
                'legend' => EQ::t("Information"),
                'fields' => [],
            ],
        ];
        $can_change = $this->config("account_page.can_change");
        foreach($model->currentRules("change") as $field) {
            if($field === "pass" || $field === "pass_confirm")
                continue;
            if(!$model->isShow($field))
                continue;
            $fields['info']['fields'][$field] = [
                'type' => $model->typeFormControl($field),
                'label' => $model->fieldLabel($field),
                'value' => $model->fieldValue($field),
                'disabled' => !in_array($field, $can_change),
            ];
        }
        if($this->config("use_password")) {
            $fields['pass'] = [
                'legend' => EQ::t("Change password"),
                'fields' => [
                    'pass' => [
                        'type' => "passwordField",
                        'label' => EQ::t("New password"),
                    ],
                    'pass_confirm' => [
                        'type' => "passwordField",
                        'label' => EQ::t("Confirm password"),
                    ],
                ],
            ];
        }
        if($this->config("account_page.can_set_theme")) {
            $fields['ui'] = [
                'legend' => EQ::t("Interface"),
                'fields' => [
                    'settings_theme' => [
                        'type' => "select",
                        'label' => EQ::t("Theme"),
                        'variants' => $this->config("account_page.available_themes"),
                        'value' => $model->settingsGet("theme", EQ::app()->theme->name),
                    ],
                ],
            ];
        }
        return EQ::widget($this->config("account_page.form_widget"), $fields);
    }

}
