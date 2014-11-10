<?php

namespace eq\data;

use eq\helpers\Str;

class ModelRelation
{

    /**
     * @var ModelBase
     */
    protected $parent;

    /**
     * @var string|ModelBase
     */
    protected $related;

    protected $fields = [];
    protected $sort = [];
    protected $func;
    protected $_custom = false;

    protected $loaded = false;
    protected $value = null;

    protected function __construct(ModelBase $parent, $related, $fields, $sort, $func)
    {
        $this->parent = $parent;
        if($related)
            $this->related = Str::className($related);
        $this->fields = $fields;
        $this->func = is_string($func) && !strncmp($func, ":", 1)
            ? [$this, substr($func, 1)] : $func;
    }

    public static function belongsTo(ModelBase $parent, $related, $fields)
    {
        return new ModelRelation($parent, $related, $fields, [], ":_belongsTo");
    }

    public static function hasMany(ModelBase $parent, $related, $fields, $sort = [])
    {
        return new ModelRelation($parent, $related, $fields, $sort, ":_hasMany");
    }

    public static function count(ModelBase $parent, $related, $fields)
    {
        return new ModelRelation($parent, $related, $fields, [], ":_count");
    }

    public static function custom(ModelBase $parent, $func)
    {
        $rel = new ModelRelation($parent, '', [], [], $func);
        $rel->_custom = true;
        return $rel;
    }

    public function isCustom()
    {
        return $this->_custom;
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    public function getValue()
    {
        if(!$this->loaded) {
            $this->value = call_user_func($this->func);
            $this->loaded = true;
        }
        return $this->value;
    }

    public function setValue($value)
    {
        $this->value = $value;
        $this->loaded = true;
    }

    protected function _belongsTo()
    {
        $rclass = $this->related;
        return $rclass::findRelatedOne($this->createCondition());
    }

    protected function _hasMany()
    {
        $rclass = $this->related;
        return $rclass::findRelatedAll($this->createCondition());
    }

    protected function _count()
    {
        $rclass = $this->related;
        return $rclass::countRelated($this->createCondition());
    }

    protected function createCondition()
    {
        $fields = [];
        foreach($this->fields as $pname => $rname)
            $fields[$rname] = $this->parent->{$pname};
        return $fields;
    }

} 