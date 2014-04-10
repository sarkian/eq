<?php
/**
 * Last Change: 2014 Apr 09, 14:55
 */

namespace eq\widgets;

use eq\web\html\Html;
use eq\helpers\Str;
use eq\web\WidgetException;
use eq\modules\clog\Clog;

class FormBase extends \eq\web\WidgetBase
{

    use \eq\base\TObject;

    private static $_forms = [];

    protected $values = [];
    protected $labels = [];
    protected $_id;

    public function getId()
    {
        if(!$this->_id)
            $this->createId();
        return $this->_id;
    }

    public function setValues($values)
    {
        $this->values = $values;
    }

    public function setLabels($labels)
    {
        $this->labels = $labels;
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
        // $name = preg_replace_callback("/_([a-zA-Z])/", function($m) {
            // return " ".strtoupper($m[1]);
        // }, $name);
        // return ucfirst($name);
    }

    public function fieldValue($name, $default = "")
    {
        return isset($this->values[$name]) ? $this->values[$name] : $default;
    }

    public function begin($options = [])
    {
        $options = $this->formOptions() + $options;
        return Html::tag("form", $options, null, false);
    }

    public function end()
    {
        return "</form>";
    }

    public function textField($name, $options = [])
    {
        $options = array_merge($this->inputOptions([
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
        $options = array_merge($this->inputOptions([
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
        $options = array_merge($this->inputOptions([
            'type' => "submit",
        ], "submitButton"), $options);
        return Html::tag("button", $options, htmlspecialchars($text));
    }

    public function label($name, $options = [], $type = null)
    {
        $options = array_merge($this->labelOptions([
            'for' => $this->fieldId($name),
        ], $type, $name));
        return Html::tag("label", $options, $this->fieldLabel($name));
    }

    public function renderField($name, $type = null)
    {
        if($method = $this->getFieldMethod($name, "Render"))
            $contents = $this->{$method}();
        elseif($method = $this->getInputMethod($type, "Render"))
            $contents = $this->{$method}($name);
        elseif($type && method_exists($this, $type))
            $contents = $this->renderLabel($name, $type).$this->{$type}($name);
        else
            throw new WidgetException("Cant render field: $name (type: $type)");
        return $this->inputWrap($contents, $type, $name);
    }

    public function renderLabel($name, $type = null)
    {
        if($method = $this->getFieldMethod($name, "RenderLabel"))
            return $this->{$method}();
        elseif($method = $this->getInputMethod($type, "RenderLabel"))
            return $this->{$method}($name);
        else
            return $this->label($name);
    }

    protected final function createId()
    {
        $cname = get_class($this);
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
            'method' => "POST",
            'action' => "",
        ];
    }

    protected function inputOptions($options, $type, $name = null)
    {
        $method = $this->getInputMethod($type, "Options");
        if($method)
            $options = array_merge($options, $this->{$method}($name));
        $method = $this->getFieldMethod($name, "Options");
        if($method)
            $options = array_merge($options, $this->{$method}());
        return $options;
    }

    protected function labelOptions($options, $type, $name = null)
    {
        $method = $this->getInputMethod($type, "LabelOptions");
        if($method)
            $options = array_merge($options, $this->{$method}($name));
        $method = $this->getFieldMethod($name, "LabelOptions");
        if($method)
            $options = array_merge($options, $this->{$method}());
        return $options;
    }

    protected function inputWrap($contents, $type,
                            $name = null, &$wrapped = null)
    {
        $wrapped = true;
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

}
