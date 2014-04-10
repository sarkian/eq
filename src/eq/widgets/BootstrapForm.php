<?php
/**
 * Last Change: 2014 Apr 09, 13:52
 */

namespace eq\widgets;

use eq\web\html\Html;

class BootstrapForm extends FormBase
{

    protected function formOptions()
    {
        return [
            'role' => "form",
            'method' => "POST",
            'action' => "",
        ];
    }

    protected function inputOptions($options, $type, $name = null)
    {
        return array_merge([
            'class' => "form-control",
        ], parent::inputOptions($options, $type, $name));
    }

    protected function inputSubmitButtonOptions($name)
    {
        return [
            'class' => "btn btn-default",
        ];
    }

    protected function inputWrap($contents, $type, 
                            $name = null, &$wrapped_ = null)
    {
        $contents = parent::inputWrap($contents, $type, $name, $wrapped);
        if(!$wrapped)
            return Html::tag("div", ['class' => "form-group"], $contents);
        return $contents;
    }

    protected function inputSubmitButtonWrap($contents, $name = null)
    {
        return $contents;
    }

}
