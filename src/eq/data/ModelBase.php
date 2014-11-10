<?php

namespace eq\data;

use EQ;
use eq\base\InvalidCallException;
use eq\base\Loader;
use eq\base\NotImplementedException;
use eq\base\Object;
use eq\base\TEvent;
use eq\base\UnknownPropertyException;
use eq\datatypes\DataTypeBase;
use eq\db\DbException;
use eq\helpers\Arr;
use eq\helpers\Str;

/**
 * @property array fields
 * @property string db_name
 * @property array fieldnames
 * @property array saved_fields
 * @property array loaded_fields
 * @property array visible_fields
 * @property array saved_fieldnames
 * @property array loaded_fieldnames
 * @property array visible_fieldnames
 * @property string pk
 * @property array rules
 * @property array errors
 * @property array errors_by_field
 * @property string scenario
 * @property array messages
 * @property int page_size
 * @property ModelRelation[] relations
 */
abstract class ModelBase extends Object
{

    use TEvent;

    protected $scenario = "default";
    protected $data = [];
    protected $changed_fields = [];
    protected $loaded_data = [];
    protected $errors = [];
    protected $errors_by_field = [];
    protected $_deleted = false;

    /**
     * @var ModelRelation[]
     */
    protected $_relations = null;
    protected $fields = null;

    abstract public function getFields();

    abstract protected function insertQuery(array $cols);
    abstract protected function updateQuery(array $cols, $condition);
    abstract protected function selectQuery(array $cols, $condition, array $options = []);
    abstract protected function deleteQuery($condition);
    abstract protected function lastInsertId();
    abstract protected function pkCondition();

    public function __construct($scenario = null)
    {
        $fields = $this->fields === null ? $this->getFields() : $this->fields;
        foreach($fields as $name => $field) {
            $field = $this->normalizeFieldData($field);
            if(isset($field['default']))
                $this->data[$name] = $field['default'];
            $this->fields[$name] = $field;
        }
        if($scenario)
            $this->setScenario($scenario);
        $this->_relations = $this->relations;
    }

    /**
     * @param string $scenario
     * @return static
     */
    public static function i($scenario = null)
    {
        $cname = get_called_class();
        return new $cname($scenario);
    }

    /**
     * @param array $data
     * @param string $scenario
     * @return Provider
     */
    public static function provider($data = [], $scenario = null)
    {
        $cname = get_called_class();
        $ns = explode('\\', $cname);
        $cbasename = array_pop($ns) . "Provider";
        $subns = array_pop($ns);
        $ns_arr = $subns === "models" ? ["providers", $cbasename] : [$subns, $cbasename];
        $pname = implode('\\', array_diff(array_merge($ns, $ns_arr), [null]));
        Loader::classExists($pname) or $pname = 'eq\data\Provider';
        return new $pname($data, $cname, $scenario);
    }

    /**
     * @param mixed $condition
     * @return static
     */
    public static function find($condition)
    {
        return static::i()->load($condition);
    }

    /**
     * @param mixed $condition
     * @param array|object $data
     * @param bool $save
     * @return static
     * @throws \eq\db\DbException
     */
    public static function findOrCreate($condition, $data = [], $save = false)
    {
        $model = static::i();
        if($model->load($condition))
            return $model;
        if(is_array($data) && empty($data))
            $data = is_array($condition) ? $condition : [$model->pk => $condition];
        $model->apply($data);
        if($save)
            $model->save();
        return $model;
    }

    public static function findRelatedOne(array $condition)
    {
        return static::find($condition);
    }

    /**
     * @param array $condition
     * @param array $sort
     * @return mixed
     * @throws NotImplementedException
     */
    public static function findRelatedAll(array $condition, array $sort = [])
    {
        throw new NotImplementedException();
    }

    /**
     * @param array $condition
     * @return int
     * @throws NotImplementedException
     */
    public static function countRelated(array $condition)
    {
        throw new NotImplementedException();
    }

    public static function belongsTo(ModelBase $parent, array $fields)
    {
        return ModelRelation::belongsTo($parent, get_called_class(), $fields);
    }

    public static function hasMany(ModelBase $parent, array $fields, array $sort = [])
    {
        return ModelRelation::hasMany($parent, get_called_class(), $fields, $sort);
    }

    public static function countRelation(ModelBase $parent, array $fields)
    {
        return ModelRelation::count($parent, get_called_class(), $fields);
    }

    public static function customRelation(ModelBase $parent, $func)
    {
        return ModelRelation::custom($parent, $func);
    }

    public function isRelatedLoaded($name)
    {
        return isset($this->_relations[$name]) ? $this->_relations[$name]->isLoaded() : false;
    }

    public function __get($name)
    {
        if($this->getterExists($name))
            return parent::__get($name);
        if(isset($this->_relations[$name]))
            return $this->_relations[$name]->getValue();
        $this->field($name);
        return isset($this->data[$name]) ? $this->data[$name] : $this->fieldDefault($name);
    }

    public function __set($name, $value)
    {
        if($this->setterExists($name)) {
            $this->setChanged($name);
            parent::__set($name, $value);
        }
        elseif(isset($this->_relations[$name])) {
            $this->_relations[$name]->setValue($value);
        }
        else {
            $this->field($name);
            if(!$this->isChange($name))
                throw new InvalidCallException(
                    "Property is not modifiable on current scenario: "
                    .get_class($this)."::".$name);
            $this->setChanged($name);
            $this->data[$name] = $value;
        }
    }

    public function __isset($name)
    {
        if(parent::__isset($name))
            return true;
        if(isset($this->_relations[$name]))
            return true;
        return $this->field($name, false) !== null;
    }

    public function propertySet($name, $value)
    {
        $this->field($name);
        $this->setChanged($name);
        $this->data[$name] = $value;
    }

    public function field($name, $throw = true, $default = null)
    {
        if(!isset($this->fields[$name])) {
            if($throw)
                throw new UnknownPropertyException("Unknown field: ".get_called_class()."::".$name);
            else
                return $default;
        }
        else
            return $this->fields[$name];
    }

    public function fieldsToSave()
    {
        return $this->loaded_data
            ? array_intersect($this->changed_fields, $this->saved_fieldnames)
            : $this->saved_fieldnames;
    }

    public function save()
    {
        $this->validate();
        if($this->errors)
            return false;
        $this->trigger("beforeSave");
        $fields = $this->fieldsToSave();
        if(!$fields) {
            $this->trigger("saveCancel");
            return true;
        }
        $cols = [];
        foreach($fields as $name)
            $cols[$name] = $this->{$name};
//            $cols[$name] = $this->typeToDb($name, $this->{$name});
        if($this->loaded_data)
            $res = $this->updateQuery($cols, $this->pkCondition());
        else
            $res = $this->insertQuery($cols);
        if($res) {
            $pk = $this->{$this->pk};
            if($pk === null || (!is_numeric($pk) && !$pk))
                $this->data[$this->pk] = $this->typeToDb(
                    $this->pk, $this->lastInsertId());
            $this->loaded_data = $this->data;
            $this->trigger("saveSuccess");
            return true;
        }
        else {
            $this->trigger("saveFail");
            return false;
        }
    }

    public function load($condition)
    {
        $condition = $this->processLoadCondition($condition);
        $data = $this->selectQuery($this->loaded_fieldnames, $condition);
        if(!is_array($data)) {
            if(is_object($data)) {
                if($data instanceof \Traversable)
                    $data = iterator_to_array($data);
                else
                    throw new InvalidCallException(
                        "Invalid ".get_called_class()."::selectQuery() result: ".get_class($data));
            }
            else
                $data = [];
        }
        $data = array_values($data);
        if(!count($data))
            return false;
        elseif(count($data) > 1)
            throw new DbException("Non unique load result");
        foreach($data[0] as $name => $value)
            $this->data[$name] = $this->loaded_data[$name]
                = $this->typeFromDb($name, $value);
        $this->changed_fields = [];
        return $this;
    }

    public function reload()
    {
        $this->load($this->pkCondition());
    }

    public function delete()
    {
        if(!$this->isLoaded())
            return false;
        $this->_deleted = $this->deleteQuery($this->pkCondition());
        return $this->_deleted;
    }

    public function getPageSize()
    {
        return 10;
    }

    public function getVisibleFields()
    {
        return $this->fieldsByAttr("show");
    }

    public function getLoadedFields()
    {
        return $this->fieldsByAttr("load");
    }

    public function getSavedFields()
    {
        return $this->fieldsByAttr("save");
    }

    public function getDbName()
    {
        return null;
    }

    public function getPk()
    {
        return "id";
    }

    public function getMessages()
    {
        return [];
    }

    public function getScenario()
    {
        return $this->scenario;
    }

    public function setScenario($scenario)
    {
        if(!is_string($scenario) || !strlen($scenario))
            return $this;
        $method = "scenario" . ucfirst($scenario);
        if(method_exists($this, $method))
            $this->$method();
        $this->scenario = $scenario;
        return $this;
    }

    public function getFieldnames()
    {
        return array_keys($this->fields);
    }

    public function getVisibleFieldnames()
    {
        return array_keys($this->visible_fields);
    }

    public function getLoadedFieldnames()
    {
        return array_keys($this->loaded_fields);
    }

    public function getSavedFieldnames()
    {
        return array_keys($this->saved_fields);
    }

    public function getRules()
    {
        return [
//            'default' => ['change' => "*"],
            'default' => ['change' => array_keys($this->fields)],
        ];
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorsByField()
    {
        return $this->errors_by_field;
    }

    public function getRelations()
    {
        return [];
    }

    public function apply($data, $shown_only = false)
    {
        $this->trigger("beforeApply", [$data]);
        $data = $this->normalizeData($data);
        foreach($data as $name => $value) {
            if($shown_only && !$this->isShow($name))
                continue;
            if($this->fieldExists($name) && $this->isChange($name)) {
                $this->setChanged($name);
                if($this->setterExists($name))
                    parent::__set($name, $value);
                else
                    $this->data[$name] = $value;
            }
        }
        $this->trigger("afterApply", [$data]);
        return $this;
    }

    protected function normalizeData($data)
    {
        if(is_array($data) && isset($data[0])) {
            $fields = $this->fieldnames;
            if(count($data) < count($fields))
                $fields = array_slice($fields, 0, count($data));
            elseif(count($data) > count($fields))
                $data = array_slice($data, 0, count($fields));
            $data = array_combine($fields, $data);
        }
        return $data;
    }

    public function applyLoaded($data)
    {
        foreach($data as $name => $value) {
            if($this->field($name, false) && $this->isLoad($name))
                $this->data[$name] = $this->loaded_data[$name] = $this->typeFromDb($name, $value);
        }
        $this->changed_fields = [];
        return $this;
    }

    public function applyAll($data)
    {
        foreach($data as $name => $value) {
            if($this->fieldExists($name)) {
                $this->setChanged($name);
                if($this->setterExists($name))
                    parent::__set($name, $value);
                else
                    $this->data[$name] = $value;
            }
        }
        return $this;
    }

    public function reset()
    {
        foreach($this->fields as $name => $params)
            $this->data[$name] = isset($params['default']) ? $params['default'] : null;
        $this->changed_fields = [];
        $this->loaded_data = [];
        $this->errors = [];
        $this->errors_by_field = [];
        return $this;
    }

    public function clearErrors()
    {
        $this->errors = [];
        $this->errors_by_field = [];
        return $this;
    }

    public function clearFieldErrors($name)
    {
        unset($this->errors_by_field[$name]);
        $this->errors = array_filter($this->errors, function($err) use($name) {
            return !(isset($err['field']) && $err['field'] === $name);
        });
    }

    public function validate()
    {
        $this->trigger("beforeValidate");
        if($this->errors)
            return false;
        $unique = [];
        foreach($this->currentRules("change") as $name) {
            $value = $this->{$name};
            $method = "validate".Str::var2method($name);
            if(method_exists($this, $method)) {
                $err = $this->{$method}($value);
                if(is_string($err) && strlen($err))
                    $this->addRawError($err, $name);
            }
            elseif($this->typeIsEmpty($name, $value)) {
                if($this->isRequired($name)) {
                    $this->addError("required", $name);
                }
            }
            elseif(!$this->typeValidate($name, $value))
                $this->addError("invalid", $name);
            elseif($this->isUnique($name))
                $unique[$name] = $value;
        }
        if($unique) {
            $this->validateUnique($unique);
        }
        $this->trigger("afterValidate");
        return $this->errors ? false : true;
    }

    public function validateField($name)
    {
        // FIXME: copypaste!
        $value = $this->{$name};
        $method = "validate".Str::var2method($name);
        if(method_exists($this, $method)) {
            $err = $this->{$method}($value);
            if(is_string($err) && strlen($err))
                $this->addRawError($err, $name);
        }
        elseif($this->isRequired($name) && $this->typeIsEmpty($name, $value))
            $this->addError("required", $name);
        elseif(!$this->typeValidate($name, $value))
            $this->addError("invalid", $name);
        elseif($this->isUnique($name))
            $this->validateUnique([$name => $value]);
        return $this;
    }

    public function addError($type, $field)
    {
        $message = $this->errorMessage($type, $field);
        $this->addRawError($message, $field);
    }

    public function addRawError($message, $field = null)
    {
        $error = ['message' => $message, 'field' => $field];
        if(in_array($error, $this->errors))
            return;
        array_push($this->errors, $error);
        if($field /* && isset($this->data[$field]) */ )
            $this->errors_by_field[$field][] = $message;
    }

    public function isChange($field)
    {
        return in_array($field, $this->currentRules("change"));
    }

    public function isRequired($field)
    {
        return in_array($field, $this->currentRules("required"));
    }

    public function isUnique($field)
    {
//        if($this->fieldProperty($field, "unique") === true)
//            return true;
        return in_array($field, $this->currentRules("unique"));
    }

    public function isShow($field)
    {
        return $this->fieldProperty($field, "show", false);
    }

    public function isLoad($field)
    {
        return $this->fieldProperty($field, "load", false);
    }

    public function isSave($field)
    {
        return $this->fieldProperty($field, "save", false);
    }

    public function fieldExists($field)
    {
        return $this->field($field, false) !== null;
    }

    public function fieldLabel($name)
    {
        if($this->fieldExists($name)) {
            $field = $this->normalizeFieldData($this->field($name));
            if(isset($field['label']))
                return $field['label'];
        }
        $name = preg_replace_callback("/_([a-zA-Z])/", function ($m) {
            return " " . strtoupper($m[1]);
        }, $name);
        return ucfirst($name);
    }

    public function fieldTypename($fieldname)
    {
        $field = $this->normalizeFieldData($this->field($fieldname));
        return isset($field['type']) ? $field['type'] : "str";
    }

    /**
     * @param $fieldname
     * @return DataTypeBase|string
     * @throws \eq\base\InvalidParamException
     */
    public function fieldType($fieldname)
    {
        return DataTypeBase::getClass($this->fieldTypename($fieldname));
    }

    public function fieldValue($name, $default = null)
    {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    public function fieldDefault($name)
    {
        if(!$this->fieldExists($name))
            return null;
        $field = $this->field($name);
        return isset($field['default']) ? $field['default'] : $this->typeDefaultValue($name);
    }

    public function fieldProperty($name, $prop, $default = null)
    {
        if(!$this->fieldExists($name))
            return $default;
        $field = $this->normalizeFieldData($this->field($name));
        return isset($field[$prop]) ? $field[$prop] : $default;
    }

    public function fieldErrors($name)
    {
        if(isset($this->errors_by_field[$name]) && is_array($this->errors_by_field[$name]))
            return $this->errors_by_field[$name];
        else
            return [];
    }

    public function typeIsEmpty($fieldname, $value)
    {
        $vmethod = "isEmpty" . Str::var2method($fieldname);
        if(method_exists($this, $vmethod))
            return $this->{$vmethod}($value);
        elseif(property_exists($this, $vmethod) && is_callable($this->{$vmethod}))
            return call_user_func_array($this->{$vmethod}, [$value]);
        $type = $this->fieldType($fieldname);
        return $type::isEmpty($value);
    }

    public function typeValidate($fieldname, $value)
    {
        $vmethod = "validate".Str::var2method($fieldname);
        if(method_exists($this, $vmethod))
            return $this->{$vmethod}($value);
        elseif(property_exists($this, $vmethod) && is_callable($this->{$vmethod}))
            return call_user_func_array($this->{$vmethod}, [$value]);
        $type = $this->fieldType($fieldname);
        return $type::validate($value);
    }

    public function typeFilter($fieldname, $value)
    {
        $type = $this->fieldType($fieldname);
        return $type::filter($value);
    }

    public function typeFromDb($fieldname, $value)
    {
        $type = $this->fieldType($fieldname);
        return $type::fromDb($value);
    }

    public function typeToDb($fieldname, $value)
    {
        $type = $this->fieldType($fieldname);
        return $type::toDb($value);
    }

    public function typeFormControl($fieldname)
    {
        if(isset($this->fields[$fieldname]['form_control']))
            return $this->fields[$fieldname]['form_control'];
        $type = $this->fieldType($fieldname);
        return $type::formControl();
    }

    public function typeFormControlOptions($fieldname)
    {
        if(isset($this->fields[$fieldname]['form_control_options']))
            return $this->fields[$fieldname]['form_control_options'];
        $type = $this->fieldType($fieldname);
        return $type::formControlOptions();
    }

    public function typeDefaultValue($fieldname)
    {
        $type = $this->fieldType($fieldname);
        return $type::defaultValue();
    }

    public function _dump()
    {
        // TODO: use fullDump
        // TODO: dump changed/loaded
        var_dump($this->data);
    }

    protected function validateUnique($fields)
    {

    }

    protected function fieldsByAttr($attr, $value = null)
    {
        $fields = [];
        foreach($this->fields as $name => $field) {
            $field = $this->normalizeFieldData($field);
            if(!isset($field[$attr]))
                continue;
            if($value === null) {
                if($field[$attr])
                    $fields[$name] = $field;
            }
            else {
                if($field[$attr] === $value)
                    $fields[$name] = $field;
            }
        }
        return $fields;
    }

    protected function setChanged($field)
    {
        if(!$this->isChanged($field))
            $this->changed_fields[] = $field;
    }

    protected function unsetChanged($field)
    {
        $key = array_search($field, $this->changed_fields);
        if($key !== false)
            unset($this->changed_fields[$key]);
    }

    public function isChanged($field = null)
    {
        return $field === null
            ? (bool) count($this->changed_fields) : in_array($field, $this->changed_fields);
    }

    public function isLoaded($field = null)
    {
        return $field === null
            ? (bool) count($this->loaded_data) : isset($this->loaded_data[$field]);
    }

    public function currentRules($type = null, $default = [])
    {
        $rules = isset($this->rules[$this->scenario]) && is_array($this->rules[$this->scenario])
            ? $this->rules[$this->scenario] : $default;
        if(!$type)
            return $rules;
        return isset($rules[$type]) && gettype($rules[$type]) === gettype($default)
            ? $rules[$type] : $default;
    }

    public function filterCondition($condition)
    {
        return is_array($condition) ? array_intersect_key($condition, $this->fields) : $condition;
    }

    protected function processLoadCondition($condition)
    {
        if(is_array($condition))
            return $condition;
        else
            return [$this->pk => $condition];
    }

    protected function processCondition($condition)
    {
        if(is_array($condition)) {
            foreach($condition as $name => $value)
                $condition[$name] = $this->typeToDb($name, $value);
        }
        return $condition;
    }

    protected function errorMessage($type, $field)
    {
        $message = Arr::getItem($this->messages, [$type, $field]);
        return $message ? $message : $this->defaultErrorMessage($type, $field);
    }

    protected function defaultErrorMessage($type, $field)
    {
        return EQ::t(ucfirst($type) . " field") . ": " . $this->fieldLabel($field);
    }

    protected function normalizeFieldData($field)
    {
        if(is_array($field)) {
            if(isset($field[0])) {
                $data = [
                    'type' => $field[0],
                    'load' => true,
                    'save' => true,
                ];
                if(isset($field[1]))
                    $data['sql'] = $field[1];
                return $data;
            }
            else {
                if(!isset($field['load']))
                    $field['load'] = true;
                if(!isset($field['save']))
                    $field['save'] = true;
                return $field;
            }
        }
        else
            return [
                'type' => $field,
                'load' => true,
                'save' => true,
                'show' => $field === $this->pk ? false : true,
            ];
    }

    protected function _saveEvents()
    {
        return false;
    }

}