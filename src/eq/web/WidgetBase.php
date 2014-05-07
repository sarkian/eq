<?php
/**
 * Last Change: 2014 Apr 30, 20:22
 */

namespace eq\web;

use EQ;
use eq\base\TObject;
use eq\helpers\Str;
use eq\helpers\Path;
use eq\cgen\ViewRenderer;
use eq\web\WidgetException;

abstract class WidgetBase
{

    use TObject;

    const _FILE_ = __FILE__;
    const _DIR_ = __DIR__;

    protected $_file_ = __FILE__;
    protected $_dir_ = __DIR__;

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
            $fname = Path::join([
                $class::_DIR_,
                "views",
                Str::method2cmd(Str::classBasename($class)),
                "$view.php"
            ]);
            if(file_exists($fname))
                return $fname;
        }
        throw new WidgetException("View file not found: $view");
    }

}
