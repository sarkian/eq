<?php
/**
 * Last Change: 2014 May 04, 04:58
 */

namespace eq\widgets;

use EQ;
use eq\base\TObject;
use eq\data\Model;
use eq\helpers\Str;

class ModelForm extends FormBase
{

    use TObject;

    private static $_forms = [];

    /**
     * @var Model model
     */
    protected $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
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
        $this->errors = $this->model->errors;
        $this->errors_by_field = $this->model->errors_by_field;
        $out = [$this->begin()];
        foreach($this->model->currentRules("change") as $field) {
            if(!$this->model->isVisible($field))
                continue;
            $type = $this->model->typeFormControl($field);
            $out[] = $this->renderField($field, $type);
        }
        $out[] = $this->submitButton(
            $this->model->currentRules("submit_text",
                EQ::t(Str::method2label($this->model->scenario))));
        $out[] = $this->end();
        return implode("\n", $out);
    }

    public function getData()
    {
        $data = [];
        foreach($this->model->currentRules("change") as $name) {
            if(!$this->model->isVisible($name))
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
