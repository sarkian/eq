<?php

namespace eq\modules\user\models;

use eq\orm\Model;
use eq\web\IIdentity;

class SqlUser extends Model implements IIdentity
{

    use TUser;

    public function getTableName()
    {
        return $this->module->table_name;
    }

} 