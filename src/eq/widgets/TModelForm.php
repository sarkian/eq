<?php
/**
 * Last Change: 2014 Apr 17, 15:02
 */

namespace eq\widgets;

use EQ;
use eq\data\Model;
use eq\web\html\Html;
use eq\helpers\Str;
use eq\modules\clog\Clog;

trait TModelForm
{

    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function fieldLabel($name)
    {
        return $this->model->fieldLabel($name);
    }

    public function render()
    {
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

}
