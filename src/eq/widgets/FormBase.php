<?php

namespace eq\widgets;

use eq\base\TObject;
use eq\helpers\Arr;
use eq\web\html\Html;
use eq\helpers\Str;
use eq\web\html\HtmlNode;
use eq\web\WidgetBase;
use eq\web\WidgetException;
use EQ;

/**
 * @property string id
 * @property string method
 * @property array errors
 * @property array errors_by_field
 */
class FormBase extends WidgetBase
{

    use TObject;

    private static $_forms = [];

    protected $options = [];

    protected $values = [];
    protected $labels = [];
    protected $_id;
    protected $_autofocus = false;

    public function __construct($options = [])
    {
        $this->options = Arr::extend($options, [
            'type' => "normal",
            'label_width' => 2,
            'control_width' => 10,
        ]);
    }

    public function getId()
    {
        if(!$this->_id)
            $this->createId();
        return $this->_id;
    }

    public function getMethod()
    {
        return "POST";
    }

    public function setValues($values)
    {
        $this->values = $values;
    }

    public function setLabels($labels)
    {
        $this->labels = $labels;
    }

    public function getErrors()
    {
        return [];
    }

    public function getErrorsByField()
    {
        return [];
    }

    public function fieldName($name)
    {
        return $name;
    }

    public function fieldLabel($name)
    {
        if(isset($this->labels[$name]))
            return $this->labels[$name];
        return Str::method2label($name);
    }

    public function fieldValue($name, $default = "")
    {
        return isset($this->values[$name])
            ? htmlspecialchars($this->values[$name]) : $default;
    }

    public function fieldErrors($name)
    {
        if(isset($this->errors_by_field[$name]) && is_array($this->errors_by_field[$name]))
            return $this->errors_by_field[$name];
        else
            return [];
    }

    public function begin($options = [])
    {
        $options = $this->formOptions() + $options;
        $html = Html::tag("form", $options, null, false);
        $html .= $this->renderErrors();
        return $html;
    }

    public function end()
    {
        return "</form>";
    }

    public function textArea($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'id' => $this->fieldId($name),
            'name' => $this->fieldName($name),
        ], "textArea", $name), $options);
        return Html::tag("textarea", $options, htmlspecialchars($this->fieldValue($name)));
    }

    public function textField($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'id' => $this->fieldId($name),
            'type' => "text",
            'name' => $this->fieldName($name),
            'placeholder' => $this->fieldLabel($name),
            'value' => htmlspecialchars($this->fieldValue($name)),
        ], "textField", $name), $options);
        return Html::tag("input", $options);
    }

    public function passwordField($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'id' => $this->fieldId($name),
            'type' => "password",
            'name' => $this->fieldName($name),
            'placeholder' => $this->fieldLabel($name),
            'value' => htmlspecialchars($this->fieldValue($name)),
        ], "passwordField", $name), $options);
        return Html::tag("input", $options);
    }

    public function submitButton($text, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'type' => "submit",
        ], "submitButton"), $options);
        return Html::tag("button", $options, htmlspecialchars($text));
    }

    public function button($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'type' => "button",
            'id' => $this->fieldId($name),
            'name' => $this->fieldName($name),
            'value' => htmlspecialchars($this->fieldValue($name, $this->fieldLabel($name))),
        ], "button"), $options);
        return Html::tag("input", $options);
    }

    public function label($name, $options = [], $type = null)
    {
        $options = Html::mergeAttrs($this->labelOptions([
            'for' => $this->fieldId($name),
        ], $type, $name), $options);
        return Html::tag("label", $options, $this->fieldLabel($name));
    }

    public function select($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'id' => $this->fieldId($name),
            'name' => $this->fieldName($name),
            'value' => htmlspecialchars($this->fieldValue($name)),
            '#variants' => [],
        ], "select", $name), $options);
        $variants = $options['#variants'];
        if(is_callable($variants))
            $variants = $variants();
        $sel = new HtmlNode("select", $options);
        foreach($variants as $value => $text)
            $sel->append($this->renderSelectOption($value, $text, $options['value']));
        return $sel->render(true);
    }

    public function checkBox($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'type' => "checkbox",
            'id' => $this->fieldId($name),
            'name' => $this->fieldName($name),
            'value' => $this->fieldValue($name, false),
        ], "checkBox", $name), $options);
        if($options['value'])
            $options['checked'] = "checked";
        unset($options['value']);
        return Html::tag("input", $options);
    }

    public function radioButton($name, $options = [])
    {
        // TODO: Implement
    }

    public function hidden($name, $options = [])
    {
        $options = Html::mergeAttrs($this->inputOptions([
            'type' => "hidden",
            'id' => $this->fieldId($name),
            'name' => $this->fieldName($name),
            'value' => $this->fieldValue($name),
        ], "hidden", $name), $options);
        return Html::tag("input", $options);
    }

    public function fieldsetBegin($legend = null)
    {
        $html = "<fieldset>";
        return $legend === null ? $html : $html."<legend>".htmlspecialchars($legend)."</legend>";
    }

    public function fieldsetEnd()
    {
        return "</fieldset>";
    }

    public function renderField($name, $type = null, $options = [])
    {
        if(!$this->_autofocus
            && $this->typeCanHasAutofocus($type) && $this->nameCanHasAutofocus($name)
            && (($this->errors_by_field && $this->fieldErrors($name)) || !$this->errors_by_field)
        ) {
            $options['autofocus'] = "autofocus";
            $this->_autofocus = true;
        }
        if(($method = $this->getFieldMethod($name, "Render")))
            $contents = $this->{$method}($options);
        elseif(($method = $this->getInputMethod($type, "Render")))
            $contents = $this->{$method}($name, $options);
        else
            $contents = $this->renderFieldDefault($name, $type, $options);
        return $this->rowWrap($contents, $type, $name, $options);
    }

    public function renderFieldDefault($name, $type = null, $options = [])
    {
        if($type && method_exists($this, $type))
            return $this->labelWrap($this->renderLabel($name, $type), $type, $name, $options)
                .$this->inputWrap($this->{$type}($name, $options), $type, $name, $options);
        else
            throw new WidgetException("Cant render field: $name (type: $type)");
    }

    public function renderLabel($name, $type = null)
    {
        if(($method = $this->getFieldMethod($name, "RenderLabel")))
            return $this->{$method}();
        elseif(($method = $this->getInputMethod($type, "RenderLabel")))
            return $this->{$method}($name);
        elseif($this->typeCanHasLabel($type) && $this->nameCanHasLabel($name))
            return $this->label($name, [], $type);
        else
            return "";
    }

    protected function nameCanHasLabel($name)
    {
        return true;
    }

    protected function typeCanHasLabel($type)
    {
        if($type === null)
            return true;
        return in_array($type, [
            "textField",
            "numberField",
            "passwordField",
            "select",
            "checkBox",
            "textArea",
        ]);
    }

    protected function nameCanHasAutofocus($name)
    {
        return true;
    }

    protected function typeCanHasAutofocus($type)
    {
        if(is_null($type))
            return true;
        return in_array($type, [
            "textField",
            "numberField",
            "passwordField",
        ]);
    }

    protected function createId()
    {
        $id = Str::method2var(Str::classBasename(get_class($this)));
        if(isset(self::$_forms[$id]))
            $id .= "-".(++self::$_forms[$id]);
        else
            self::$_forms[$id] = 0;
        $this->_id = $id;
    }

    protected function fieldId($name)
    {
        return $this->id."-".$name;
    }

    protected function formOptions()
    {
        return [
            'role' => "form",
            'method' => $this->method,
            'action' => "",
            'id' => $this->id,
        ];
    }

    protected function inputOptions($options, $type, $name = null)
    {
        $method = $this->getInputMethod($type, "Options");
        if($method)
            $options = Html::mergeAttrs($options, $this->{$method}($name));
        $method = $this->getFieldMethod($name, "Options");
        if($method)
            $options = Html::mergeAttrs($options, $this->{$method}());
        return $options;
    }

    protected function labelOptions($options, $type, $name = null)
    {
        $method = $this->getInputMethod($type, "LabelOptions");
        if($method)
            $options = Html::mergeAttrs($options, $this->{$method}($name));
        $method = $this->getFieldMethod($name, "LabelOptions");
        if($method)
            $options = Html::mergeAttrs($options, $this->{$method}());
        return $options;
    }

    protected function labelWrap($contents, $type, $name = null, $options = [], &$wrapped = null)
    {
        $wrapped = false;
        return $contents;
    }

    protected function inputWrap($contents, $type, $name = null, $options = [], &$wrapped = null)
    {
        $wrapped = false;
        return $contents;
    }

    protected function rowWrap($contents, $type, $name = null, $options = [], &$wrapped = null)
    {
        $wrapped = true;
        if($contents === null)
            return "";
        $method = $this->getFieldMethod($name, "Wrap");
        if($method)
            return $this->{$method}($contents);
        $method = $this->getInputMethod($type, "Wrap");
        if($method)
            return $this->{$method}($contents, $name);
        $wrapped = false;
        return $contents;
    }

    protected function getInputMethod($type, $method_)
    {
        if(!$type)
            return false;
        $method = "input".ucfirst($type).$method_;
        return method_exists($this, $method) ? $method : false;
    }

    protected function getFieldMethod($name, $method_)
    {
        if(!$name)
            return false;
        $method = "field".Str::var2method($name).$method_;
        return method_exists($this, $method) ? $method : false;
    }

    /**
     * @return HtmlNode|string
     */
    protected function renderErrors()
    {
        if(!$this->errors)
            return "";
        $errors = $this->errorsContainer();
        EQ::assert($errors instanceof HtmlNode,
            'errorsContainer() must be returns an eq\web\html\HtmlNode instance');
        foreach($this->errors as $error) {
            $message = "";
            $field = null;
            if(is_array($error)) {
                if(!isset($error['message']))
                    continue;
                $message = $error['message'];
                if(isset($error['field']))
                    $field = $error['field'];
            } elseif(is_string($error))
                $message = $error;
            if(!$message)
                continue;
            if(!is_string($field) || !$field)
                $field = null;
            $errors->append($this->renderError($message, $field));
        }
        return $errors->getContents() ? $errors : "";
    }

    /**
     * @return HtmlNode
     */
    protected function errorsContainer()
    {
        return new HtmlNode("ul");
    }

    /**
     * @param string $message
     * @param string $field
     * @return HtmlNode|string
     */
    protected function renderError($message, $field = null)
    {
        return Html::tag("li", [], $message);
    }

    protected function renderSelectOption($value, $opt, $selected)
    {
        if(is_array($opt)) {
            $group = new HtmlNode("optgroup", ['label' => $value]);
            foreach($opt as $v => $t)
                $group->append($this->renderSelectOption($v, $t, $selected));
            return $group->render();
        }
        else {
            $opt = new HtmlNode("option", ['value' => htmlspecialchars($value)], htmlspecialchars($opt));
            if($value == $selected)
                $opt->attr("selected", "selected");
            return $opt->render();
        }
    }

}
