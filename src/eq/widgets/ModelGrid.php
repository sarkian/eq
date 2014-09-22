<?php

namespace eq\widgets;

use eq\data\ModelBase;
use eq\data\Provider;

class ModelGrid extends GridBase
{

    protected $provider;
    protected $model;

    public function __construct(Provider $provider)
    {
        $this->provider = $provider;
        $this->model = $provider->emptyModel();
    }

    public function render()
    {
        $res = $this->provider->map([$this, "renderRow"]);
        return implode("\n", $res);
    }

    public function renderRow(ModelBase $model)
    {

    }

} 