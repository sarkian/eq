<?php

namespace eq\web;

use EQ;
use eq\assets\eq\BaseAsset;
use eq\base\TObject;
use eq\helpers\Debug;
use eq\web\html\Html;
use eq\assets\JqueryAsset;
use eq\helpers\FileSystem;

class ClientScript
{

    use TObject;

    const POS_HEAD = 1;
    const POS_BEGIN = 2;
    const POS_END = 3;
    const POS_READY = 4;

    protected $title = "";

    private $meta_tags = [];
    private $link_tags = [];
    private $css = [];
    private $css_files = [];
    private $js = [
        self::POS_HEAD => [],
        self::POS_BEGIN => [],
        self::POS_END => [],
        self::POS_READY => [],
    ];
    private $js_files = [
        self::POS_HEAD => [],
        self::POS_BEGIN => [],
        self::POS_END => [],
    ];
    private $bundles = [];

    public function __construct($config)
    {

    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function createTitle($title = "...")
    {
        $this->title = str_replace(
            '{$TITLE}',
            $title, 
            EQ::app()->config(
                "web.title_template",
                '{$TITLE} — '.EQ::app()->config(
                    "system.app_name", 
                    EQ::app()->config("system.app_namespace")
                )
            )
        );
    }

    public function getTitle()
    {
        return $this->title;
    }

    /**
     * addMetaTag 
     * 
     * @param array $options 
     * @return ClientScript
     */
    public function addMetaTag($options)
    {
        $this->meta_tags[] = Html::tag('meta', $options);
        return $this;
    }

    /**
     * addLinkTag 
     * 
     * @param array $options 
     * @return ClientScript
     */
    public function addLinkTag($options)
    {
        $this->link_tags[] = Html::tag('link', $options);
        return $this;
    }

    /**
     * addCss 
     * 
     * @param string $code 
     * @param array $options 
     * @return ClientScript
     */
    public function addCss($code, $options = [])
    {
        $options = array_merge(['type' => "text/css"], $options);
        $this->css[] = Html::tag("style", $options, "\n$code\n");
        return $this;
    }

    /**
     * addCssFile 
     * 
     * @param string $url 
     * @param array $options 
     * @return ClientScript
     */
    public function addCssFile($url, $options = [])
    {
        $options = array_merge([
            'rel' => "stylesheet",
            'type' => "text/css",
            'href' => $url,
        ], $options);
        $this->css_files[] = Html::tag("link", $options);
        return $this;
    }

    /**
     * addJs 
     * 
     * @param string $code 
     * @param int $position 
     * @return ClientScript
     */
    public function addJs($code, $position = self::POS_HEAD)
    {
        if(EQ_DBG) {
            $location = Debug::callLocation(1);
            $info = "[".$location[0].":".$location[1]."]";
            $code = "/* >$info */\n$code\n/* /$info */";
        }
        $this->js[$position][] = $code;
    }

    /**
     * addJsFile 
     * 
     * @param string $url 
     * @param int $position
     * @return ClientScript
     */
    public function addJsFile($url, $position = self::POS_HEAD)
    {
        if($position === self::POS_READY)
            $this->addJs(FileSystem::fgets("@www$url"));
        else {
            $this->js_files[$position][] = Html::tag("script",
                ['type' => "text/javascript", 'src' => $url], null, false)
                ."</script>";
        }
    }

    /**
     * @param \eq\web\AssetBundle|string $bundle
     * @param bool $reload
     * @return \eq\web\ClientScript
     */
    public function addBundle($bundle, $reload = EQ_ASSETS_DBG)
    {
        if(!$bundle instanceof AssetBundle) {
            $cname = AssetBundle::getClass($bundle);
            $bundle = new $cname();
        }
        else
            $cname = get_class($bundle);
        if(!isset($this->bundles[$cname])) {
            $bundle->registerAssets($reload);
            $this->bundles[$cname] = $bundle;
        }
        return $this;
    }

    public function notify($message, $type = "info", $options = [])
    {
        BaseAsset::register();
        $message = json_encode($message);
        $type = json_encode($type);
        $options = json_encode($options, JSON_FORCE_OBJECT);
        $this->addJs("EQ.notify($message, $type, $options);", self::POS_READY);
    }

    public function renderHead()
    {
        if($this->js[self::POS_READY]) {
            JqueryAsset::register();
            $ready_script = [];
            foreach($this->js[self::POS_READY] as $script)
                $ready_script[] = "$(function() {\n$script\n});";
            $this->addJs(implode("\n\n", $ready_script));
        }
        $html = [];
        if($this->title)
            $html[] = Html::tag('title', [], $this->title);
        $html[] = $this->joinTags($this->meta_tags);
        $html[] = $this->joinTags($this->link_tags);
        $html[] = $this->joinTags($this->css_files);
        $html[] = $this->joinTags($this->css);
        $html[] = $this->joinTags($this->js_files[self::POS_HEAD]);
        if($this->js[self::POS_HEAD])
            $html[] = $this->joinScripts(self::POS_HEAD);
        return $this->joinTags($html, true);
    }

    public function renderBegin()
    {
        $html = [];
        if($this->js[self::POS_BEGIN]) {
            $html[] = Html::tag("script", ['type' => "text/javascript"],
                implode("\n\n", $this->js[self::POS_BEGIN]));
        }
        $html[] = $this->joinTags($this->js_files[self::POS_BEGIN]);
        return $this->joinTags($html);
    }

    public function renderEnd()
    {
        $html = [];
        $html[] = $this->joinTags($this->js_files[self::POS_END]);
        $html[] = $this->joinTags($this->js[self::POS_END]);
        return $this->joinTags($html, true);
    }

    private function joinTags($tags, $ret = false)
    {
        $tags = array_diff($tags, ['']);
        if(empty($tags)) return '';
        $tags = implode("\n", $tags);
        return $ret ? $tags."\n" : $tags; 
    }

    private function joinScripts($pos, $tag = true)
    {
        $code = "\n".implode("\n\n", $this->js[$pos])."\n";
        if($tag)
            return Html::tag("script", ['type' => "text/javascript"], $code);
        else
            return $code;
    }

}
