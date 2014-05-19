<?php

namespace eq\themes\bootstrap\widgets;

use eq\web\html\Html;
use eq\web\html\HtmlNode;

class ModelForm extends \eq\widgets\ModelForm
{

    protected function inputOptions($options, $type, $name = null)
    {
        return array_merge([
            'class' => "form-control",
        ], parent::inputOptions($options, $type, $name));
    }

    protected function inputSubmitButtonOptions(/** @noinspection PhpUnusedParameterInspection */
        $name)
    {
        return [
            'class' => "btn btn-default",
        ];
    }

    protected function inputTextFieldLabelOptions()
    {
        return ['class' => "control-label"];
    }

    protected function inputWrap($contents, $type, $name = null, &$wrapped_ = null)
    {
        $contents = parent::inputWrap($contents, $type, $name, $wrapped);
        if(!$wrapped) {
            $class = ["form-group"];
            if($name && $this->fieldErrors($name)) {
                $class[] = "has-error";
                $class[] = "has-feedback";
                $contents .= Html::tag("span", [
                    'class' => ["glyphicon", "glyphicon-remove", "form-control-feedback"],
                ], "");
            }
            return Html::tag("div", ['class' => $class], $contents);
        }
        return $contents;
    }

    protected function inputSubmitButtonWrap($contents, /** @noinspection PhpUnusedParameterInspection */
                                             $name = null)
    {
        return $contents;
    }

    protected function errorsContainer()
    {
        return new HtmlNode("fieldset");
    }

    protected function renderError($message, $field = null)
    {
        return Html::tag("div", ['class' => "alert alert-danger"], $message);
    }

}
