<?php

namespace eq\mongodb;

use EQ;
use eq\helpers\Arr;
use MongoClient;

class Connection
{

    /**
     * @var MongoClient
     */
    protected $client = null;
    protected $string;
    protected $default_db;

    public function __construct(array $config)
    {
        $config = Arr::extend($config, [
            'host' => MongoClient::DEFAULT_HOST,
            'port' => MongoClient::DEFAULT_PORT,
            'default_db' => EQ::app()->app_namespace,
        ]);
        $this->string = "mongodb://".$config['host'].":".$config['port'];
        $this->default_db = $config['default_db'];
    }

    public function open()
    {
        if($this->client)
            return;
        $this->client = new MongoClient($this->string);
    }

    public function close()
    {
        if(!$this->client)
            return;
        $this->client->close(true);
        $this->client = null;
    }

    public function call($name = null)
    {
        $name !== null or $name = $this->default_db;
        return $this->__get($name);
    }

    public function __get($name)
    {
        $this->open();
        return $this->client->selectDB($name);
    }

    public function __destruct()
    {
        $this->close();
    }

} 