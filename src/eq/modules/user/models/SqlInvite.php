<?php

namespace eq\modules\user\models;

use eq\modules\user\UserModule;
use eq\orm\Model;

class SqlInvite extends Model
{

    use TInvite;

    public function getTableName()
    {
        return UserModule::instance()->invites_table_name;
    }

}
