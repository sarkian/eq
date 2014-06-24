<?php

namespace eq\modules\user;

use EQ;
use eq\base\InvalidConfigException;
use eq\base\ModuleBase;
use eq\db\mysql\Schema;
use eq\helpers\Str;
use eq\modules\admin\AdminModule;
use eq\modules\i18n\I18nModule;
use eq\modules\navigation\NavigationModule;

/**
 * @property string|array login_field
 * @property bool managed_sessions
 */
class UserModule extends ModuleBase
{

    protected $title = "EQ User";
    protected $description = [
        'en_US' => "Authorization, user management",
        'ru_RU' => "Авторизация, управление пользователями",
    ];

    private $fields_defaults;
    private $login_field;
    private $managed_sessions = false;

    public function getComponents()
    {
        return [
            'user' => 'eq\modules\user\models\User',
        ];
    }

    protected static function preInit()
    {
        EQ::app()->bind("modules.eq:i18n.beforeLoadFiles", function (I18nModule $module) {
            $module->addDir(__DIR__."/locale", "user");
        });
    }

    public function webInit()
    {
        $site_nav = $this->config("site_nav", "site");
        $use_icons = $this->config("use_nav_icons", true);
        EQ::app()->bind("modules.eq:navigation.navRender.$site_nav",
        function(NavigationModule $module) use($use_icons) {
            $module->addItem("site", [
                'route' => "modules.eq:user.user.login",
                'title' => EQ::t("Login"),
                'icon' => $use_icons ? "user" : "",
                'perms' => "guest",
            ]);
            if($this->config("registration_enabled", true))
                $module->addItem("site", [
                    'route' => "modules.eq:user.user.register",
                    'title' => EQ::t("Register"),
                    'icon' => $use_icons ? "plus" : "",
                    'perms' => "guest",
                ]);
            $module->addItem("site", [
                'route' => "modules.eq:user.user.logout",
                'title' => EQ::t("Logout"),
                'icon' => $use_icons ? "off" : "",
                'perms' => "user,admin",
            ]);
        });
        EQ::app()->bind("modules.eq:admin.ready", function(AdminModule $module) {
            $items = [];
            $items[] = [
                'route' => $this->route("admin.index"),
                'title' => EQ::t("Manage"),
            ];
            if($this->config("use_invites", false)) {
                $items[] = [
                    'route' => $this->route("invites.index"),
                    'title' => EQ::t("Invites"),
                ];
            }
            $module->addPage("users", [
                'title' => EQ::t("Users"),
                'items' => $items,
            ]);
            $module->addPage("modules", [
                'title' => $this->title,
            ]);
        });
    }

    public function getUrlPrefix()
    {
        return $this->config("url_prefix", "");
    }

    public function getFields()
    {
        $fields = [
            'id' => $this->field("id"),
        ];
        foreach($this->config("fields", []) as $name => $attrs) {
            $fields[$name] = $this->field($name);
        }
        $this->login_field = $this->config("login_field", "name");
        if(!$this->login_field)
            throw new InvalidConfigException("Invalid login field");
        if(is_array($this->login_field)) {
            foreach($this->login_field as $field) {
                if(!isset($fields[$field]))
                    $fields[$field] = $this->field($field);
            }
        }
        elseif(!isset($fields[$this->login_field]))
            $fields[$this->login_field] = $this->field($this->login_field);
        // email
        if($this->config("use_email", false)) {
            $fields['email'] = $this->field("email");
            if($this->config("email_confirmation", false) && !isset($fields['email_confirm']))
                $fields['email_confirm'] = $this->field("email_confirm");
            if($this->config("email_verification", true)) {
                // TODO Email verification
            }
        }
        // password
        if($this->config("use_password", true)) {
            $fields['pass'] = $this->field("pass");
            if($this->config("password_confirmation", true) && !isset($fields['pass_confirm']))
                $fields['pass_confirm'] = $this->field("pass_confirm");
        }
        // phone
        if($this->config("use_phone", false)) {
            $fields['phone'] = $this->field("phone");
        }
        // firstname
        if($this->config("use_firstname", false)) {
            $fields['firstname'] = $this->field("firstname");
        }
        // lastname
        if($this->config("use_lastname", false)) {
            $fields['lastname'] = $this->field("lastname");
        }
        // invite
        if($this->config("use_invites", false)) {
            $fields['invite'] = $this->field("invite");
        }
        if($this->config("use_role", true)) {
            $fields['role'] = $this->field("role");
        }
        $this->managed_sessions = $this->config("managed_sessions", false);
        return $fields;
    }

    public function getLoginField()
    {
        if(!$this->login_field)
            $this->getFields();
        return $this->login_field;
    }

    public function getManagedSessions()
    {
        return $this->managed_sessions;
    }

    protected function field($name)
    {
        $field = $this->config("fields.$name", []);
        $defaults = $this->fieldDefaults($name);
        foreach($defaults as $aname => $aval) {
            if(!isset($field[$aname]))
                $field[$aname] = $aval;
        }
        return $field;
    }

    protected function fieldDefaults($name)
    {
        if(!$this->fields_defaults)
            $this->fields_defaults = $this->fieldsDefaults();
        $attrs = isset($this->fields_defaults[$name]) ? $this->fields_defaults[$name] : [];
        if(is_string($attrs))
            $attrs = $this->fields_defaults[$attrs];
        if(!isset($attrs['type']) || !$attrs['type'])
            $attrs['type'] = "str";
        if(!isset($attrs['show']))
            $attrs['show'] = true;
        if(!isset($attrs['load']))
            $attrs['load'] = true;
        if(!isset($attrs['save']))
            $attrs['save'] = true;
        if(!isset($attrs['required']))
            $attrs['required'] = true;
        if(!isset($attrs['unique']))
            $attrs['unique'] = false;
        if(!isset($attrs['default']))
            $attrs['default'] = "";
        if(!isset($attrs['label']))
            $attrs['label'] = EQ::t(Str::method2label($name));
        return $attrs;
    }

    private function fieldsDefaults()
    {
        return [
            'id' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => false,
                'unique' => true,
                'default' => null,
                'label' => "",
                'sql' => Schema::TYPE_PK,
            ],
            'name' => [
                'type' => "username",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => true,
                'default' => "",
                'label' => EQ::t("Username"),
            ],
            'email' => [
                'type' => "email",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => true,
                'default' => "",
                'label' => EQ::t("Email"),
            ],
            'email_confirm' => [
                'type' => "email",
                'show' => true,
                'load' => false,
                'save' => false,
                'default' => "",
            ],
            'firstname' => [
                'type' => "firstname",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => false,
                'default' => "",
                'label' => EQ::t("First Name"),
            ],
            'lastname' => [
                'type' => "lastname",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => false,
                'default' => "",
                'label' => EQ::t("Last Name"),
            ],
            'phone' => [
                'type' => "phone",
                'show' => true,
                'load' => true,
                'save' => true,
                'unique' => true,
                'default' => "",
                'label' => EQ::t("Phone"),
            ],
            'role' => [
                'type' => "uintp",
                'show' => false,
                'load' => true,
                'save' => true,
                'default' => 0,
                'label' => EQ::t("Role"),
            ],
            'pass' => [
                'type' => "password",
                'show' => true,
                'load' => true,
                'save' => true,
                'default' => "",
                'label' => EQ::t("Password"),
            ],
            'pass_confirm' => [
                'type' => "password",
                'show' => true,
                'load' => false,
                'save' => false,
                'default' => "",
                'label' => EQ::t("Password confirmation"),
            ],
            'invite' => [
                'type' => "invite",
                'show' => true,
                'load' => false,
                'save' => false,
                'default' => "",
                'label' => EQ::t("Invite"),
            ],
        ];
    }

}
