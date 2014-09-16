<?php

namespace eq\modules\user\models;

use eq\mongodb\Document;
use eq\web\IIdentity;

class MongoUser extends Document implements IIdentity
{

    use TUser;

    public function getCollectionName()
    {
        return $this->module->collection_name;
    }

} 