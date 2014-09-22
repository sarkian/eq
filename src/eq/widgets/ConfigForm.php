<?php

namespace eq\widgets;

use EQ;
use eq\web\html\Html;

class ConfigForm extends FormBase
{

    protected $fields = [];
    protected $_fields = [];

    protected $_autofocus = true;

    public function __construct($fields, $options = [])
    {
        $this->fields = $fields;
        foreach($fields as $field) {
            if(is_array($field) && isset($field['fields']) && is_array($field['fields']))
                $this->_fields = array_merge($this->_fields, $field['fields']);
        }
        parent::__construct($options);
    }

    public function fieldLabel($name)
    {
        return $this->fieldParam($name, "label", $name);
    }

    public function fieldValue($name, $default = "")
    {
        return $this->fieldParam($name, "value", $default);
    }

    public function fieldType($name)
    {
        return $this->fieldParam($name, "type", "textfield");
    }

    public function fieldIsDisabled($name)
    {
        return $this->fieldParam($name, "disabled", false);
    }

    public function render()
    {
        $out = [$this->begin()];
        foreach($this->fields as $name => $field) {
            if(isset($field['legend'], $field['fields']))
                $out[] = $this->renderFieldset($field);
            else
                $out[] = $this->renderField($name, $this->fieldType($name));
        }
        $out[] = $this->submitButton(isset($this->options['submit_text'])
            ? $this->options['submit_text'] : EQ::t("Save"));
        $out[] = $this->end();
        return implode("\n", $out);
    }

    public function getData()
    {
        $data = [];
        foreach($this->_fields as $name => $field) {
            if($this->fieldIsDisabled($name))
                continue;
            $type = $this->fieldType($name);
            $value = EQ::app()->request->post($name);
            if($type === "select") {
                $variants = $this->fieldParam($name, "variants", []);
                $data[$name] = isset($variants[$value]) ? $value : $this->fieldValue($name);
            }
            else
                $data[$name] = $value;
        }
        return $data;
    }

    protected function renderFieldset($fieldset)
    {
        $out = [$this->fieldsetBegin($fieldset['legend'])];
        foreach($fieldset['fields'] as $fieldname => $field) {
            $out[] = $this->renderField($fieldname, $this->fieldType($fieldname));
        }
        $out[] = $this->fieldsetEnd();
        return implode("\n", $out);
    }

    protected function inputOptions($options, $type, $name = null)
    {
        $opts = $name === null ? [] : $this->fieldParam($name, "options", []);
        if($name !== null) {
            if($this->fieldIsDisabled($name))
                $opts['disabled'] = "disabled";
            if($type === "select")
                $options['variants'] = $this->fieldParam($name, "variants", []);
        }
        return Html::mergeAttrs($opts, parent::inputOptions($options, $type, $name));
    }

    protected function formOptions()
    {
        $opts = ['class' => "eq-config-form"];
        if(isset($this->options['action']))
            $opts['action'] = $this->options['action'];
        return Html::mergeAttrs($opts, parent::formOptions());
    }

    protected function fieldParam($fieldname, $paramname, $default = null)
    {
        return isset($this->_fields[$fieldname][$paramname])
            ? $this->_fields[$fieldname][$paramname] : $default;
    }

}