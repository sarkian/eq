<?php

namespace eq\data;

use EQ;
use eq\base\InvalidCallException;
use eq\base\InvalidParamException;
use eq\base\Object;
use eq\base\TEvent;
use eq\base\UnknownPropertyException;
use eq\datatypes\DataTypeBase;
use eq\db\ConnectionBase;
use eq\db\DbException;
use eq\db\Query;
use eq\db\SQLException;
use eq\helpers\Arr;
use eq\helpers\Str;

/**
 * @property array fields
 * @property string db_name
 * @property string table_name
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
 */
abstract class Model extends Object
{

    use TEvent;

    private $create_table = false;

    protected $scenario = "default";
    /**
     * @var ConnectionBase
     */
    protected $db;
    protected $data = [];
    protected $changed_fields = [];
    protected $loaded_data = [];
    protected $errors = [];
    protected $errors_by_field = [];

    abstract public function getFields();

    public function __construct($scenario = null)
    {
        $this->db = EQ::app()->db($this->db_name);
        foreach($this->fields as $name => $field) {
            $field = $this->normalizeFieldData($field);
            if(isset($field['default']))
                $this->data[$name] = $field['default'];
        }
        if($scenario)
            $this->setScenario($scenario);
    }

    /**
     * @param string $scenario
     * @return $this
     */
    public static function instance($scenario = null)
    {
        $cname = get_called_class();
        return new $cname($scenario);
    }

    public function __get($name)
    {
        if($this->getterExists($name))
            return parent::__get($name);
        if(!isset($this->fields[$name]))
            throw new UnknownPropertyException(
                "Unknown property: ".get_class($this)."::".$name);
        return isset($this->data[$name]) ? $this->data[$name] : $this->fieldDefault($name);
    }

    public function __set($name, $value)
    {
        if($this->setterExists($name)) {
            $this->setChanged($name);
            parent::__set($name, $value);
        }
        if(!isset($this->fields[$name]))
            throw new UnknownPropertyException(
                "Setting unknown property: ".get_class($this)."::".$name);
        if(!$this->isChange($name))
            throw new InvalidCallException(
                "Property is not modifiable on current scenario: "
                .get_class($this)."::".$name);
        $this->setChanged($name);
        $this->data[$name] = $value;
    }

    public function __isset($name)
    {
        if(parent::__isset($name))
            return true;
        return isset($this->fields[$name]);
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
        return "main";
    }

    public function getTableName()
    {
        return Str::method2var(Str::classBasename(get_called_class()));
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
        $method = "scenario".ucfirst($scenario);
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
            'default' => [
                'change' => $this->fieldnames,
            ],
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

    public function load($condition)
    {
        $condition = $this->processLoadCondition($condition);
        $res = $this->executeQuery(
            $this->db->select($this->loaded_fieldnames)->from($this->table_name)->where($condition)
        );
        if(!$res->rowCount())
            return false;
        elseif($res->rowCount() > 1)
            throw new DbException("Non unique load result");
        foreach($res->fetch() as $name => $value)
            $this->data[$name] = $this->loaded_data[$name]
                = $this->typeFromDb($name, $value);
        $this->changed_fields = [];
        return $this;
    }

    public function exists($condition)
    {
        return (bool) $this->count($condition);
    }

    public function count($condition)
    {
        $condition = $this->processLoadCondition($condition);
        $res = $this->executeQuery(
            $this->db->select("COUNT(*)")->from($this->table_name)->where($condition)
        );
        return (int) $res->fetchColumn();
    }

    public function apply($data)
    {
        $this->trigger("beforeApply", [$data]);
        if(isset($data[0]))
            $data = array_combine($this->fieldnames, $data);
        foreach($data as $name => $value) {
            if(isset($this->fields[$name]) && $this->isChange($name)) {
                $this->setChanged($name);
                $this->data[$name] = $value;
            }
        }
        $this->trigger("afterApply", [$data]);
        return $this;
    }

    public function applyAll($data)
    {
        foreach($data as $name => $value) {
            if(isset($this->fields[$name])) {
                $this->setChanged($name);
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

    public function validate()
    {
        $this->trigger("beforeValidate");
        $unique = [];
        foreach($this->currentRules("change") as $name) {
            $value = $this->{$name};
            if($this->isRequired($name) && $this->typeIsEmpty($name, $value))
                $this->addError("required", $name);
            elseif(!$this->typeValidate($name, $value))
                $this->addError("invalid", $name);
            elseif($this->isUnique($name))
                $unique[$name] = $value;
        }
        if($unique) {
            $cols = [];
            foreach($unique as $name => $value)
                $cols[$name] = $this->typeToDb($name, $value);
            $condition = $this->db->schema->buildCondition($cols, "=", "OR");
            if($this->loaded_data) {
                $condition = "(".$condition.") AND ".$this->pkCondition("<>");
            }
            $res = $this->executeQuery(
                $this->db->select(array_keys($unique))
                    ->from($this->table_name)->where($condition, [], "OR")
            );
            foreach($res->fetchAll() as $item) {
                foreach($item as $iname => $ivalue) {
                    $ivalue = $this->typeFromDb($iname, $ivalue);
                    if($unique[$iname] === $ivalue)
                        $this->addError("unique", $iname);
                }
            }
        }
        $this->trigger("afterValidate");
    }

    public function save()
    {
        $this->validate();
        if($this->errors)
            return false;
        $this->trigger("beforeSave");
        $fields = $this->loaded_data
            ? array_intersect($this->changed_fields, $this->saved_fieldnames)
            : $this->saved_fieldnames;
        $cols = [];
        foreach($fields as $name)
            $cols[$name] = $this->typeToDb($name, $this->{$name});
        // $this->db->pdo->beginTransaction();
        if($this->loaded_data)
            $res = $this->executeQuery(
                $this->db->update($this->table_name, $cols)
                    ->where($this->pkCondition())
            );
        else
            $res = $this->executeQuery($this->db->insert($this->table_name, $cols));
        // $this->db->pdo->commit();
        if($res->rowCount()) {
            $pk = $this->{$this->pk};
            if($pk === null || (!is_numeric($pk) && !$pk))
                $this->data[$this->pk] = $this->typeToDb(
                    $this->pk, $this->db->pdo->lastInsertId($this->pk));
            $this->loaded_data = $this->data;
            $this->trigger("saveSuccess");
            return true;
        }
        else {
            $this->trigger("saveFail");
            return false;
        }
    }

    public function delete()
    {
        if(!$this->loaded_data)
            throw new InvalidCallException("Cant delete not loaded model");
        $res = $this->db->delete($this->table_name, $this->pkCondition())->execute();
        return (bool) $res->rowCount();
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
        if($field && isset($this->data[$field]))
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
        return isset($this->fields[$field]);
    }

    public function fieldLabel($name)
    {
        if(isset($this->fields[$name])) {
            $field = $this->normalizeFieldData($this->fields[$name]);
            if(isset($field['label']))
                return $field['label'];
        }
        $name = preg_replace_callback("/_([a-zA-Z])/", function ($m) {
            return " ".strtoupper($m[1]);
        }, $name);
        return ucfirst($name);
    }

    /**
     * @param $fieldname
     * @return DataTypeBase|string
     * @throws \eq\base\InvalidParamException
     */
    public function fieldType($fieldname)
    {
        if(!isset($this->fields[$fieldname]))
            throw new InvalidParamException("Unknown field: $fieldname");
        $field = $this->normalizeFieldData($this->fields[$fieldname]);
        $type = isset($field['type']) ? $field['type'] : "str";
        return DataTypeBase::getClass($type);
    }

    public function fieldValue($name, $default = null)
    {
        return isset($this->data[$name]) ? $this->data[$name] : $default;
    }

    public function fieldDefault($name)
    {
        return $this->fieldProperty($name, "default");
    }

    public function fieldProperty($name, $prop, $default = null)
    {
        if(!isset($this->fields[$name]))
            return $default;
        $field = $this->normalizeFieldData($this->fields[$name]);
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
        $vmethod = "isEmpty".Str::var2method($fieldname);
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

    public function typeSqlType($fieldname)
    {
        $type = $this->fieldType($fieldname);
        return $type::sqlType($this->db->driver);
    }

    public function typeFormControl($fieldname)
    {
        $type = $this->fieldType($fieldname);
        return $type::formControl();
    }

    public function typeFormControlOptions($fieldname)
    {
        $type = $this->fieldType($fieldname);
        return $type::formControlOptions();
    }

    public function createTable()
    {
        $cols = [];
        foreach($this->fieldnames as $field) {
            if(!$this->isLoad($field) && !$this->isSave($field))
                continue;
            $type = $this->fieldType($field);
            $cols[$field] = $type;
        }
        $this->db->createTable($this->table_name, $cols)->execute();
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

    protected function pkCondition($operator = "=")
    {
        return $this->db->schema->buildCondition(
            [$this->pk => $this->typeToDb($this->pk, $this->{$this->pk})],
            $operator
        );
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

    protected function isChanged($field)
    {
        return in_array($field, $this->changed_fields);
    }

    public function currentRules($type = null, $default = [])
    {
        $rules = isset($this->rules[$this->scenario])
        && is_array($this->rules[$this->scenario])
            ? $this->rules[$this->scenario] : $default;
        if(!$type)
            return $rules;
        return isset($rules[$type])
        && gettype($rules[$type]) === gettype($default)
            ? $rules[$type] : $default;
    }

    protected function processLoadCondition($condition)
    {
        if(is_array($condition))
            foreach($condition as $name => $value)
                $condition[$name] = $this->typeToDb($name, $value);
        else
            $condition = [$this->pk => $this->typeToDb($this->pk, $condition)];
        return $condition;
    }

    protected function errorMessage($type, $field)
    {
        $message = Arr::getItem($this->messages, [$type, $field]);
        return $message ? $message : $this->defaultErrorMessage($type, $field);
    }

    protected function defaultErrorMessage($type, $field)
    {
        return EQ::t(ucfirst($type)." field").": ".$this->fieldLabel($field);
    }

    protected function executeQuery(Query $query)
    {
        try {
            return $query->execute();
        }
        catch(SQLException $e) {
            if(!EQ::app()->config("db.auto_create_table", false) || $this->create_table)
                throw $e;
            if($this->db->tableExists($this->table_name))
                throw $e;
            $this->create_table = true;
            $this->createTable();
            return $query->execute();
        }
    }

    protected function normalizeFieldData($field)
    {
        return is_array($field) ? $field : [
            'type' => $field,
            'load' => true,
            'save' => true,
        ];
    }

}
