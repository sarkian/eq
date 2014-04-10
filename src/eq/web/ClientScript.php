<?php
/**
 * Last Change: 2014 Apr 10, 13:40
 */

namespace eq\web;

use EQ;
use eq\web\html\Html;
use eq\base\InvalidArgumentException;
use eq\base\LoaderException;
use eq\base\ClientScriptException;
use eq\assets\JqueryAsset;
use eq\helpers\FileSystem;

/**
 * ClientScript 
 * 
 * @author Sarkian <root@dustus.org> 
 * @doc docs/eq/web/ClientScript.md
 * @test tests/eq/web/ClientScriptTest.php
 */
class ClientScript
{

    use \eq\base\TObject;

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
     * @return eq\web\ClientScript
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
     * @return eq\web\ClientScript
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
     * @return eq\web\ClientScript
     */
    public function addCss($code, $options = [])
    {
        $options = array_merge(['type' => 'text/css'], $options);
        $this->css[] = Html::tag('style', $options, "\n$code\n");
        return $this;
    }

    /**
     * addCssFile 
     * 
     * @param string $url 
     * @param array $options 
     * @return eq\web\ClientScript
     */
    public function addCssFile($url, $options = [])
    {
        $options = array_merge([
            'rel' => 'stylesheet',
            'type' => 'text/css',
            'href' => $url,
        ], $options);
        $this->css_files[] = Html::tag('link', $options);
        return $this;
    }

    /**
     * addJs 
     * 
     * @param string $code 
     * @param int $position 
     * @return eq\web\ClientScript
     */
    public function addJs($code, $position = self::POS_HEAD)
    {
        $this->js[$position][] = Html::tag("script", 
            ['type' => "text/javascript"], "\n$code\n");
    }

    /**
     * addJsFile 
     * 
     * @param string $url 
     * @param int $position
     * @return eq\web\ClientScript
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
     * addBundle 
     * 
     * @param eq\web\AssetBundle $bundle
     * @return eq\web\ClientScript
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

    public function renderHead()
    {
        if($this->js[self::POS_READY]) {
            JqueryAsset::register($this);
            $ready_script = [];
            foreach($this->js[self::POS_READY] as $script)
                $ready_script[] = "$(function() {\n$script\n});";
            $this->addJs(implode("\n", $ready_script));
        }
        $html = [];
        if($this->title)
            $html[] = Html::tag('title', [], $this->title);
        $html[] = $this->implodeTags($this->meta_tags);
        $html[] = $this->implodeTags($this->link_tags);
        $html[] = $this->implodeTags($this->css_files);
        $html[] = $this->implodeTags($this->css);
        $html[] = $this->implodeTags($this->js_files[self::POS_HEAD]);
        $html[] = $this->implodeTags($this->js[self::POS_HEAD]);
        return $this->implodeTags($html, true);
    }

    public function renderBegin()
    {

    }

    public function renderEnd()
    {

    }

    private function implodeTags($tags, $ret = false)
    {
        $tags = array_diff($tags, ['']);
        if(empty($tags)) return '';
        $tags = implode("\n", $tags);
        return $ret ? $tags."\n" : $tags; 
    }

}
