<?php
/**
 * Last Change: 2013 Nov 14, 10:57
 */

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

    protected function inputSubmitButtonOptions($name)
    {
        return [
            'class' => "btn btn-default",
        ];
    }

    protected function inputWrap($contents, $type, $name = null, &$wrapped_ = null)
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

    protected function errorsContainer()
    {
        return new HtmlNode("fieldset");
    }

    protected function renderError($message, $field = null)
    {
        return Html::tag("div", ['class' => "alert alert-danger"], $message);
    }

}
