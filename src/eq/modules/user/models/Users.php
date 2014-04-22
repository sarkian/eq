<?php
/**
 * Last Change: 2014 Apr 19, 18:53
 */

namespace eq\modules\user\models;

use EQ;

class Users
{

    use \eq\data\TModel;

    const SESSION_LIMIT = 29;
    
    const ROLE_USER     = 1;
    const ROLE_ADMIN    = 2;
    
    private static $_fields;

    public function getFields()
    {
        return [
            'id' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => false,
            ],
            'name' => [
                'type' => "username",
                'show' => true,
                'label' => EQ::t("Username"),
                'default' => "",
                'load' => true,
                'save' => true,
            ],
            'email' => [
                'type' => "email",
                'show' => true,
                'label' => EQ::t("Email"),
                'load' => true,
                'save' => true,
            ],
            'firstname' => [
                'type' => "firstname",
                'show' => true,
                'label' => EQ::t("Firstname"),
                'load' => true,
                'save' => true,
            ],
            'phone' => [
                'type' => "phone",
                'show' => true,
                'label' => EQ::t("Phone"),
                'load' => true,
                'save' => true,
            ],
            'role' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => true,
            ],
            'pass' => [
                'type' => "password",
                'show' => true,
                'label' => EQ::t("Password"),
                'load' => true,
                'save' => true,
            ],
            'pass_confirm' => [
                'type' => "password",
                'show' => true,
                'label' => EQ::t("Password confirmation"),
                'load' => false,
                'save' => false,
            ],
            'invite' => [
                'type' => "invite",
                'show' => true,
                'label' => EQ::t("Invite"),
                'load' => false,
                'save' => false,
            ],
        ];
    }

    public function _getFields()
    {
        if(self::$_fields)
            return self::$_fields;

        $fields = EQ::app()->config("modules.user.fields", [
            'name' => "username",
            'email' => "email",
            'pass' => "password",
            'pass_confirm' => "password",
        ]);
        self::$_fields = array_merge([
            'id' => "uintp",
        ], $fields);
        EQ::clog(self::$_fields);

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

    public function getDbName()
    {
        return EQ::app()->config("modules.user.db_name", "main");
    }

    public function getTableName()
    {
        return EQ::app()->config("modules.user.table_name", "users");
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

    public function login($data)
    {
        if(!$this->load(['name' => $data['name']]))
            return false;
    }

    public function verifyPassword($pass)
    {
        return $this->pass === md5(sha1($this->id).sha1($pass));
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
