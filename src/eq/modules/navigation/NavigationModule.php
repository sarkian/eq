<?php

namespace eq\modules\navigation;

use EQ;
use eq\base\ModuleBase;
use eq\helpers\Arr;

class NavigationModule extends ModuleBase
{

    public function configDefaults()
    {
        return [];
    }

    public function renderNav($name)
    {
        $this->trigger("navRender", [$this]);
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
                $item['link'] = isset($item['route'])
                    ? (isset($item['token']) && $item['token']
                        ? EQ::app()->createUrlT($item['route'])
                        : EQ::app()->createUrl($item['route']))
                    : "#";
            }
            $items[] = $item;
        }
        $nav->attr("items", $items);
        foreach($this->config("navs.$name.attrs", []) as $attr => $value)
            $nav->attr($attr, $value);
        return $nav->render();
    }

    public function appendItem($name, array $item)
    {
        if(is_array($name)) {
            foreach($name as $name_)
                $this->appendItem($name_, $item);
        }
        $this->configAppend("navs.$name.items", [$item]);
    }

    public function prependItem($name, array $item)
    {
        if(is_array($name)) {
            foreach($name as $name_)
                $this->prependItem($name_, $item);
        }
        $this->configPrepend("navs.$name.items", [$item]);
    }

    protected function configPermissions()
    {
        return [
            'navs' => "extend",
            'navs.*' => "all",
        ];
    }

}
