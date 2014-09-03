<?php

namespace eq\modules\user\models;

use eq\mongodb\Document;
use eq\web\IIdentity;

class MongoUser extends Document implements IIdentity
{

    use TUser;

    const SESSION_LIMIT = 29;

    const ROLE_USER = 1;
    const ROLE_ADMIN = 2;

    public function getCollectionName()
    {
        return $this->module->collection_name;
    }

} 