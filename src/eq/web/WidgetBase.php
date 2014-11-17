<?php

namespace eq\web;

use eq\base\TObject;
use eq\helpers\Str;
use eq\helpers\Path;
use eq\cgen\ViewRenderer;

abstract class WidgetBase
{

    use TObject;

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

    protected $attrs = [];

    /**
     * @param string $name
     * @param mixed $value
     * @return WidgetBase|mixed
     */
    public function attr($name, $value = null)
    {
        if(is_null($value))
            return isset($this->attrs[$name]) ? $this->attrs[$name] : null;
        $this->attrs[$name] = $value;
        return $this;
    }

    public function render()
    {
        return $this->renderView("main");
    }

    public function getData()
    {
        return [];
    }

    protected function renderView($view, $vars = [])
    {
        $file = $this->findViewFile($view);
        return ViewRenderer::renderFile($file, $vars);
    }

    protected function findViewFile($view)
    {
        $classes = array_values(class_parents($this));
        array_unshift($classes, get_called_class());
        foreach($classes as $class) {
            $fbasename = Path::join([
                $class::_DIR_,
                "views",
                Str::method2cmd(Str::classBasename($class)),
                $view
            ]);
            if(file_exists("$fbasename.php"))
                return "$fbasename.php";
            elseif(file_exists("$fbasename.twig"))
                return "$fbasename.twig";
        }
        throw new WidgetException("View file not found: $view");
    }

}
