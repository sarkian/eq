<?php

namespace eq\modules\user\models;

use eq\orm\Model;
use eq\web\IIdentity;

class SqlUser extends Model implements IIdentity
{

    use TUser;

    const SESSION_LIMIT = 29;

    const ROLE_USER = 1;
    const ROLE_ADMIN = 2;

    public function getTableName()
    {
        return $this->module->table_name;
    }

} 