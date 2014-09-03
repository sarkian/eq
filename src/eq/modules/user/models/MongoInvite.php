<?php

namespace eq\modules\user\models;

use eq\modules\user\UserModule;
use eq\mongodb\Document;

class MongoInvite extends Document
{

    use TInvite;

    public function getCollectionName()
    {
        return UserModule::instance()->invites_collection_name;
    }

} 