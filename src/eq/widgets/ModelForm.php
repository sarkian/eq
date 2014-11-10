<?php

namespace eq\widgets;

use EQ;
use eq\base\TObject;
use eq\data\ModelBase;
use eq\helpers\Arr;
use eq\helpers\Str;
use eq\web\html\Html;

class ModelForm extends FormBase
{

    use TObject;

    private static $_forms = [];

    /**
     * @var ModelBase model
     */
    protected $model;
    protected $autofocus = false;

    public function __construct(ModelBase $model, $options = [])
    {
        $this->model = $model;
        parent::__construct($options);
    }

    public function fieldLabel($name)
    {
        return $this->model->fieldLabel($name);
    }

    public function fieldValue($name, $default = "")
    {
        return htmlspecialchars($this->model->fieldValue($name, $default));
    }

    public function render()
    {
        return $this->begin()."\n".$this->renderFields()
            ."\n".$this->renderSubmitButton()."\n".$this->end();
    }

    public function renderFields()
    {
        $out = [];
        foreach($this->getShowedFields() as $field) {
            $type = $this->model->typeFormControl($field);
            $opts = $this->model->typeFormControlOptions($field);
            $out[] = $this->renderField($field, $type, $opts);
        }
        $out[] = Html::tag("input", ['type' => "hidden", 'name' => "_t", 'value' => EQ::app()->token]);
        return implode("\n", $out);
    }

    public function renderSubmitButton()
    {
        return $this->submitButton($this->model->currentRules("submit_text",
            EQ::t(Str::method2label($this->model->scenario))));
    }

    public function getShowedFields()
    {
        return array_filter($this->model->currentRules("change"), [$this->model, "isShow"]);
    }

    public function getErrors()
    {
        return $this->model->errors;
    }

    public function getErrorsByField()
    {
        return $this->model->errors_by_field;
    }

    public function getData()
    {
        $data = [];
        foreach($this->model->currentRules("change") as $name) {
            if(!$this->model->isShow($name))
                continue;
            $data[$name] = EQ::app()->request->post($name);
        }
        return $data;
    }

    protected function createId()
    {
        $id = Str::method2var(Str::classBasename(get_class($this->model)))
            ."-".Str::method2var(Str::classBasename(get_class($this)));
        if(isset(self::$_forms[$id]))
            $id .= "-".(++self::$_forms[$id]);
        else
            self::$_forms[$id] = 0;
        $this->_id = $id;
    }

}

