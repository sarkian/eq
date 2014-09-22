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
 * @property string db_type
 * @property string db_name
 * @property string table_name
 * @property string collection_name
 * @property string invites_table_name
 * @property string invites_collection_name
 */
class UserModule extends ModuleBase
{

    protected $title = "EQ User";
    protected $description = [
        'en_US' => "Authorization, user management",
        'ru_RU' => "Авторизация, управление пользователями",
    ];

    private $db_type;
    private $db_name;
    private $table_name;
    private $collection_name;
    private $invites_table_name;
    private $invites_collection_name;

    private $fields_defaults;
    private $login_field;
    private $managed_sessions = false;

    public function configDefaults()
    {
        return [
            'use_password' => true,
            'password_confirmation' => true,
            'use_settings' => true,
            'use_invites' => false,
            'use_email' => false,
            'email_confirmation' => false,
            'email_verification' => false,
            'use_phone' => false,
            'use_firstname' => false,
            'use_lastname' => false,
            'use_role' => true,
            'db_type' => "sql",
            'db_name' => null,
            'table_name' => "users",
            'collection_name' => "users",
            'invites_table_name' => "invites",
            'invites_collection_name' => "invites",
            'site_nav' => "site",
            'use_nav_icons' => true,
            'registration_enabled' => true,
            'url_prefix' => "",
            'fields' => [],
            'login_field' => "name",
            'managed_sessions' => false,
            'login_form_widget' => "ModelForm",
            'register_form_widget' => "ModelForm",
            'login_redirect_url' => "{main.index}",
            'register_redirect_url' => "{main.index}",
            'logout_redirect_url' => "{main.index}",
            'account_page' => [
                'enabled' => true,
                'can_change' => [],
                'can_set_theme' => false,
                'available_themes' => [],
                'menu_item_title' => null,
                'form_widget' => "ConfigForm",
            ],
            'icons' => [
                'login' => "glyphicon glyphicon-user",
                'register' => "glyphicon glyphicon-plus",
                'logout' => "glyphicon glyphicon-off",
                'account' => "glyphicon glyphicon-user",
            ],
        ];
    }

    public function getComponents()
    {
        if($this->db_type === "sql")
            return ['user' => 'eq\modules\user\models\SqlUser'];
        elseif($this->db_type === "mongo")
            return ['user' => 'eq\modules\user\models\MongoUser'];
        return [];
    }

    protected function init()
    {
        $this->db_type = strtolower($this->config("db_type"));
        if($this->db_type !== "sql" && $this->db_type !== "mongo")
            throw new InvalidConfigException("Invalid DB type: {$this->db_type}");
        $this->db_name = $this->config("db_name");
        $this->table_name = $this->config("table_name");
        $this->collection_name = $this->config("collection_name");
        $this->invites_table_name = $this->config("invites_table_name");
        $this->invites_collection_name = $this->config("invites_collection_name");
    }

    protected static function preInit()
    {
        EQ::app()->bind("modules.eq:i18n.beforeLoadFiles", function(I18nModule $module) {
            $module->addDir(__DIR__."/locale", "user");
        });
    }

    public function webInit()
    {
        $site_nav = $this->config("site_nav");
        $use_icons = $this->config("use_nav_icons");
        EQ::app()->bind("themeFirstRequest", function() {
            if($this->config("use_settings") && $this->config("account_page.can_set_theme")) {
                $theme = EQ::app()->user->settingsGet("theme");
                if($theme)
                    EQ::app()->setTheme($theme);
            }
        });
        EQ::app()->bind("modules.eq:navigation.navRender.$site_nav",
        function(NavigationModule $nav) use($use_icons) {
            $nav->appendItem("site", [
                'route' => "modules.eq:user.user.login",
                'title' => EQ::t("Login"),
                'icon' => $use_icons ? $this->config("icons.login") : "",
                'perms' => "guest",
            ]);
            if($this->config("registration_enabled"))
                $nav->appendItem("site", [
                    'route' => "modules.eq:user.user.register",
                    'title' => EQ::t("Register"),
                    'icon' => $use_icons ? $this->config("icons.register") : "",
                    'perms' => "guest",
                ]);
            if($this->config("account_page.enabled")) {
                $titlefunc = $this->config("account_page.menu_item_title");
                is_callable($titlefunc) or $titlefunc = function($u) { return $u->name; };
                $nav->appendItem("site", [
                    'route' => "modules.eq:user.user.account",
                    'title' => call_user_func($titlefunc, EQ::app()->user),
                    'icon' => $use_icons ? $this->config("icons.account") : "",
                    'perms' => "user,admin",
                ]);
            }
            $nav->appendItem("site", [
                'route' => "modules.eq:user.user.logout",
                'token' => true,
                'title' => EQ::t("Logout"),
                'icon' => $use_icons ? $this->config("icons.logout") : "",
                'perms' => "user,admin",
            ]);
        });
        EQ::app()->bind("modules.eq:admin.ready", function(AdminModule $module) {
            $items = [];
            $items[] = [
                'route' => $this->route("admin.index"),
                'title' => EQ::t("Manage"),
            ];
            if($this->config("use_invites")) {
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
        return $this->config("url_prefix");
    }

    public function getDbType()
    {
        return $this->db_type;
    }

    public function getDbName()
    {
        return $this->db_name;
    }

    public function getTableName()
    {
        return $this->table_name;
    }

    public function getCollectionName()
    {
        return $this->collection_name;
    }

    public function getInvitesTableName()
    {
        return $this->invites_table_name;
    }

    public function getInvitesCollectionName()
    {
        return $this->invites_collection_name;
    }

    public function getFields()
    {
        $fields = [
            'id' => $this->field("id"),
        ];
        foreach($this->config("fields") as $name => $attrs) {
            $fields[$name] = $this->field($name);
        }
        $this->login_field = $this->config("login_field");
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
        if($this->config("use_email")) {
            $fields['email'] = $this->field("email");
            if($this->config("email_confirmation") && !isset($fields['email_confirm']))
                $fields['email_confirm'] = $this->field("email_confirm");
            if($this->config("email_verification")) {
                // TODO Email verification
            }
        }
        // password
        if($this->config("use_password")) {
            $fields['pass'] = $this->field("pass");
            if($this->config("password_confirmation") && !isset($fields['pass_confirm']))
                $fields['pass_confirm'] = $this->field("pass_confirm");
        }
        // phone
        if($this->config("use_phone")) {
            $fields['phone'] = $this->field("phone");
        }
        // firstname
        if($this->config("use_firstname")) {
            $fields['firstname'] = $this->field("firstname");
        }
        // lastname
        if($this->config("use_lastname")) {
            $fields['lastname'] = $this->field("lastname");
        }
        // invite
        if($this->config("use_invites")) {
            $fields['invite'] = $this->field("invite");
        }
        if($this->config("use_role")) {
            $fields['role'] = $this->field("role");
        }
        // settings
        if($this->config("use_settings")) {
            $fields['settings'] = $this->field("settings");
        }
        $this->managed_sessions = $this->config("managed_sessions");
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
                'type' => $this->db_type === "mongo" ? "string" : "uintp",
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
            'settings' => [
                'type' => "arr",
                'show' => false,
                'load' => true,
                'save' => true,
                'default' => [],
                'label' => EQ::t("Settings"),
            ],
        ];
    }

}
