<?php

namespace eq\modules\navigation;

use EQ;
use eq\base\ModuleBase;
use eq\helpers\Arr;

class NavigationModule extends ModuleBase
{

    public function renderNav($name)
    {
        $this->trigger("navRender.$name", [$this]);
        $conf = Arr::extend($this->config("navs.$name", []), [
            'widget' => "Navbar",
            "items" => [],
        ]);
        $nav = EQ::widget($conf['widget']);
        $items = [];
        foreach($conf['items'] as $key => $item) {
            if(!isset($item['pattern'])) {
                if(isset($item['route']))
                    $item['pattern'] = $item['route'];
                else
                    $item['pattern'] = $key;
            }
            if(!isset($item['route']) && is_string($key)) {
                $item['route'] = $key;
            }
            if(!isset($item['link'])) {
                $item['link'] = isset($item['route']) ? EQ::app()->createUrl($item['route']) : "#";
            }
            $items[] = $item;
        }
        $nav->attr("items", $items);
        foreach($this->config("navs.$name.attrs", []) as $attr => $value)
            $nav->attr($attr, $value);
        return $nav->render();
    }

    public function addItem($name, array $item)
    {
        if(is_array($name)) {
            foreach($name as $name_)
                $this->addItem($name, $item);
        }
        $this->configAppend("navs.$name.items", [$item]);
    }

    protected function configPermissions()
    {
        return [
            'navs' => "append",
            'navs.*' => "all",
        ];
    }

}
