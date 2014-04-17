<?php
/**
 * Last Change: 2014 Apr 17, 21:43
 */

namespace eq\modules\user\models;

use EQ;

class Users
{

    use \eq\data\TModel;

    const SESSION_LIMIT = 29;
    
    const ROLE_USER     = 1;
    const ROLE_ADMIN    = 2;
    
    const ACC_NOCHECK   = 0;
    const ACC_INVALID   = 1;
    const ACC_OK        = 2;

    private static $_fields;

    public function getFields()
    {
        if(self::$_fields)
            return self::$_fields;

        $fields = EQ::app()->config("modules.user.fields", []);
        EQ::clog($fields);

        self::$_fields = [
            'id'                => "uintp",
            'name'              => "username",
            'email'             => "email",
            'firstname'         => "firstname",
            'phone'             => "phone",
            'role'              => "uintp",
            'pass'              => "password",
            'pass_confirm'      => "password",
            'invite'            => "invite",
        ];
        return self::$_fields;
    }

    public function getVisibleFields()
    {
        $fields = $this->getFields();
        unset($fields['id']);
        unset($fields['role']);
        return $fields;
    }

    public function getLabels()
    {
        return [
            'name' => EQ::t("Username"),
            'email' => EQ::t("Email"),
            'firstname' => EQ::t("Firstname"),
            'lastname' => EQ::t("Lastname"),
            'phone' => EQ::t("Phone"),
            'pass' => EQ::t("Password"),
            'pass_confirm' => EQ::t("Password confirmation"),
            'invite' => EQ::t("Invite"),
        ];
    }

    public function getDefaults()
    {
        return [
            'role' => 0,
        ];
    }

    public function getLoadedFields()
    {
        $fields = $this->getFields();
        unset($fields['pass_confirm']);
        unset($fields['invite']);
        return $fields;
    }

    public function getSavedFields()
    {
        return $this->loaded_fields;
    }

    public function getRules()
    {
        return [
            'register' => [
                'change' => ['name', 'email', 'firstname',
                        'phone', 'pass', 'pass_confirm', 'invite'],
                'required' => ['name', 'email', 'firstname',
                        'phone', 'pass', 'pass_confirm', 'invite'],
                'unique' => ['name', 'phone', 'email'],
            ],
            'login' => [
                'change' => ['name', 'pass'],
                'required' => ['name', 'pass'],
            ],
            'edit' => [
                'change' => ['firstname', 'phone'],
                'required' => ['firstname', 'phone'],
            ],
            'ajax-edit' => [
                'change' => ['email', 'firstname', 'phone',
                        'role', 'pass', 'subs'],
            ],
        ];
    }

    public function getMessages()
    {
        return [];
    }

    public function isAuth()
    {
        return $this->loadSession();
    }

    public function isAdmin()
    {
        return $this->role === self::ROLE_ADMIN;
    }

    protected function scenarioRegister()
    {
        $invite = new Invites();
        $pass = "";
        $this->bind("afterValidate", function() use(&$invite) {
            if($this->pass && $this->pass !== $this->pass_confirm)
                $this->addError("Пароли не совпадают", "pass");
            if(!$this->invite || !$invite->load($this->invite))
                $this->addError("Инвайт недействителен", "invite");
        });
        $this->bind("beforeSave", function() use(&$pass) {
            $pass = $this->pass;
            $this->pass = "";
        });
        $this->bind("saveSuccess", function() use(&$pass, &$invite) {
            $this->pass = md5(sha1($this->id).sha1($pass));
            $this->role = 1;
            $this->unbind("saveSuccess");
            if(!$this->save())
                $this->addRawError("Ошибка. Попробуйте позже.");
            else
                $invite->delete();
            $this->pass = $pass;
        });
        $this->bind("saveFail", function() use(&$pass) {
            $this->pass = $pass;
        });
    }

    protected function scenarioLogin()
    {
        
    }

    protected function saveSession()
    {
        
    }

    protected function loadSession()
    {
        
    }

}
