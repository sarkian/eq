<?php
/**
 * Last Change: 2014 Apr 16, 14:12
 */

namespace eq\data;

use EQ;
use eq\datatypes\DataTypeBase;
use eq\base\InvalidParamException;
use eq\base\UnknownPropertyException;
use eq\base\InvalidCallException;
use eq\db\DbException;
use eq\db\SQLException;
use eq\helpers\Arr;
use eq\helpers\Str;

trait TModel
{

    use \eq\base\TObject {
        __isset as protected TObject_isset;
        __get as protected TObject_get;
        __set as protected TObject_set;
    }
    use \eq\base\TEvent;

    protected $scenario = "default";
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
        foreach($this->defaults as $name => $value)
            $this->data[$name] = $value;
        if($scenario)
            $this->setScenario($scenario);
    }

    public static function __callStatic($name, $args)
    {
        $cname = get_called_class();
        $inst = new $cname();
        if(!method_exists($inst, $name))
            throw new InvalidCallException("Undefined method: $cname::$name");
        return call_user_func_array([$inst, $name], $args);
    }

    public function __get($name)
    {
        if($this->getterExists($name))
            return $this->TObject_get($name);
        if(!isset($this->fields[$name]))
            throw new UnknownPropertyException(
                "Unknown property: ".get_class($this)."::".$name);
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    public function __set($name, $value)
    {
        if($this->setterExists($name)) {
            $this->setChanged($name);
            $this->TObject_set($name);
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
        if($this->TObject_isset($name))
            return true;
        return isset($this->fields[$name]);
    }

    public function getVisibleFields()
    {
        return $this->getFields();
    }

    public function getLabels()
    {
        return [];
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

    public function getDefaults()
    {
        return [];
    }

    public function getLoadedFields()
    {
        return $this->fields;
    }

    public function getSavedFields()
    {
        return $this->fields;
    }

    public function getScenario()
    {
        return $this->scenario;
    }

    public function setScenario($scenario)
    {
        if(!is_string($scenario) || !strlen($scenario))
            return;
        $method = "scenario".ucfirst($scenario);
        if(method_exists($this, $method))
            $this->$method();
        $this->scenario = $scenario;
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

    public function load($condition)
    {
        $condition = $this->processLoadCondition($condition);
        $res = $this->db->select($this->loaded_fieldnames)
            ->from($this->table_name)->where($condition)->query();
        if(!$res->rowCount())
            return false;
        elseif($res->rowCount() > 1)
            throw new DbException("Non unique load result");
        foreach($res->fetch() as $name => $value)
            $this->data[$name] = $this->loaded_data[$name] 
                = $this->typeFromDb($name, $value);
        $this->changed_fields = [];
        return true;
    }

    public function exists($condition)
    {
        return (bool) $this->count($condition);
    }

    public function count($condition)
    {
        $condition = $this->processLoadCondition($condition);
        $res = $this->db->select("COUNT(*)")
            ->from($this->table_name)->where($condition)->query();
        return (int) $res->fetchColumn();
    }

    public function apply($data)
    {
        foreach($data as $name => $value) {
            if(isset($this->fields[$name]) && $this->isChange($name)) {
                $this->setChanged($name);
                $this->data[$name] = $value;
            }
        }
    }

    public function applyAll($data)
    {
        foreach($data as $name => $value) {
            if(isset($this->field[$name])) {
                $this->setChanged($name);
                $this->data[$name] = $value;
            }
        }
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
            $res = $this->db->select(array_keys($unique))
                ->from($this->table_name)->where($condition, [], "OR")->query();
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
        $cols = [];
        foreach($this->changed_fields as $name)
            if(isset($this->saved_fields[$name]))
                $cols[$name] = $this->typeToDb($name, $this->{$name});
        // $this->db->pdo->beginTransaction();
        if($this->loaded_data)
            $res = $this->db->update($this->table_name, $cols)
                ->where($this->pkCondition())->query();
        else
            $res = $this->db->insert($this->table_name, $cols)->query();
        // $this->db->pdo->commit();
        if($res->rowCount()) {
            if($this->{$this->pk} === null)
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
        $res = $this->db
            ->delete($this->table_name, $this->pkCondition())->query();
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

    public function isVisible($field)
    {
        return in_array($field, $this->visible_fieldnames);
    }

    public function fieldLabel($name)
    {
        if(isset($this->labels[$name]))
            return $this->labels[$name];
        $name = preg_replace_callback("/_([a-zA-Z])/", function($m) {
            return " ".strtoupper($m[1]);
        }, $name);
        return ucfirst($name);
    }

    public function fieldType($fieldname)
    {
        if(!isset($this->fields[$fieldname]))
            throw new InvalidParamException("Unknown field: $fieldname");
        return DataTypeBase::getClass($this->fields[$fieldname]);
    }

    public function typeIsEmpty($fieldname, $value)
    {
        $vmethod = "isEmpty".Str::var2method($fieldname);
        if(method_exists($this, $vmethod))
            return $this->{$vmethod}($value);
        $type = $this->fieldType($fieldname);
        return $type::isEmpty($value);
    }

    public function typeValidate($fieldname, $value)
    {
        $vmethod = "validate".Str::var2method($fieldname);
        if(method_exists($this, $vmethod))
            return $this->{$vmethod}($value);
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
        $type = $this->fieldType($fieldname);
        return $type::formControl();
    }

    public function typeFormControlOptions($fieldname)
    {
        $type = $this->fieldType($fieldname);
        return $type::formControlOptions();
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
        return $message ? $message : ucfirst($type)." field: ".$field;
    }

}
