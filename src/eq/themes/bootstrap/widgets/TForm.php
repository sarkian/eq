<?php

namespace eq\themes\bootstrap\widgets;

use EQ;
use eq\web\html\Html;
use eq\web\html\HtmlNode;

// TODO: inline form
trait TForm
{

    protected $form_control_bl = [
        "button",
        "submitButton",
        "checkBox",
    ];

    public function submitButton($text, $options = [])
    {
        if($this->options['type'] === "horizontal") {
            $class = "col-sm-offset-".$this->options['label_width']
                ." col-sm-".$this->options['control_width'];
            return Html::tag("div", ['class' => "form-group"],
                Html::tag("div", ['class' => $class],
                    parent::submitButton($text, $options)));
        }
        else {
            return parent::submitButton($text, $options);
        }
    }

    protected function inputOptions($options, $type, $name = null)
    {
        return Html::mergeAttrs([
            'class' => !in_array($type, $this->form_control_bl) ? "form-control" : "",
        ], parent::inputOptions($options, $type, $name));
    }

    protected function inputSubmitButtonOptions($name)
    {
        return [
            'class' => "btn btn-default",
        ];
    }

    protected function inputButtonOptions($name)
    {
        return [
            'class' => "btn btn-default",
        ];
    }

    protected function inputTextFieldLabelOptions()
    {
        return ['class' => "control-label"];
    }

    protected function inputWrap($contents, $type, $name = null, $options = [], &$wrapped = null)
    {
        $contents = parent::inputWrap($contents, $type, $name, $wrapped);
        if(isset($options['#addon_prepend']) || isset($options['#addon_append'])) {
            if(isset($options['#addon_prepend'])) {
                $contents = Html::tag("div", ['class' => "input-group-addon"], $options['#addon_prepend']).$contents;
            }
            if(isset($options['#addon_append'])) {
                $contents .= Html::tag("div", ['class' => "input-group-addon"], $options['#addon_append']);
            }
            $contents = Html::tag("div", ['class' => "input-group"], $contents);
        }
        if($this->options['type'] === "horizontal") {
            $class = "col-sm-".$this->options['control_width'];
            return Html::tag("div", ['class' => $class], $contents);
        }
        return $contents;
    }

    protected function rowWrap($contents, $type, $name = null, $options = [], &$wrapped = null)
    {
        $contents = parent::rowWrap($contents, $type, $name, $options, $wrapped);
        if(!$wrapped) {
            if($this->options['type'] !== "horizontal" && $type === "checkBox")
                $class = ["checkbox"];
            else
                $class = ["form-group"];
            if($name && $this->fieldErrors($name)) {
                $class[] = "has-error";
//                $class[] = "has-feedback";
//                $contents .= Html::tag("span", [
//                    'class' => ["glyphicon", "glyphicon-remove", "form-control-feedback"],
//                ], "");
            }
            return Html::tag("div", ['class' => $class], $contents);
        }
        return $contents;
    }

    protected function inputCheckBoxRender($name, $options = [])
    {
        if($this->options['type'] === "horizontal")
            return $this->renderCheckBoxHorizontal($name, $options);
        else
            return $this->renderCheckBoxNormal($name, $options);
    }

    protected function renderCheckBoxNormal($name, $options = [])
    {
        $label = new HtmlNode("label", $this->labelOptions([], "checkBox", $name));
        $label->append($this->checkBox($name, $options)." ".htmlspecialchars($this->fieldLabel($name)));
        return $label->render();
    }

    protected function renderCheckBoxHorizontal($name, $options = [])
    {
        $div = new HtmlNode("div", ['class' => "checkbox checkbox-inline"]);
        $div->append($this->checkBox($name, $options));
        return $this->labelWrap($this->renderLabel($name, "checkBox"), "checkBox", $name)
            .$this->inputWrap($div->render(), "checkBox", $name);
    }

    protected function errorsContainer()
    {
        return new HtmlNode("fieldset");
    }

    protected function renderError($message, $field = null)
    {
        return Html::tag("div", ['class' => "alert alert-danger"], $message);
    }

    protected function formOptions()
    {
        $opts = [
            'class' => $this->options['type'] === "normal" ? "" : "form-".$this->options['type'],
        ];
        return Html::mergeAttrs(parent::formOptions(), $opts);
    }

    protected function labelOptions($options, $type, $name = null)
    {
        $ftype = $this->options['type'];
        if($ftype !== "horizontal" && $type === "checkBox")
            return [];
        return [
            'class' => "control-label".($this->options['type'] === "horizontal"
                    ? " col-sm-".$this->options['label_width'] : ""),
        ];
    }

} 