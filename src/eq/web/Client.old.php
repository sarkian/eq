<?php

namespace eq\web;

    use \eq\web\html\Html;

    class Client_old
    {

        /**
         * in the head section
         */
        const POS_HEAD  = 1;
        /**
         * at the beginning of the body section
         */
        const POS_BEGIN = 2;
        /**
         * at the end of the body section
         */
        const POS_END   = 3;
        /**
         * inside a jQuery ready function
         */
        const POS_READY = 4;
        
        public $title = '';
        
        private $css = array();
        private $cssFiles = array();
        private $scripts = array(
            self::POS_HEAD => array(),
            self::POS_BEGIN => array(),
            self::POS_END => array(),
            self::POS_READY => array()
        );
        private $scriptFiles = array(
            self::POS_HEAD => array(),
            self::POS_BEGIN => array(),
            self::POS_END => array(),
            self::POS_READY => array()
        );
        private $linkTags = array();
        private $metaTags = array();
        private $coreScripts = array();
        private $coreStyles  = array();
        private $coreImages  = array();
        private $htmlIns = array(
            self::POS_BEGIN => array(),
            self::POS_END => array()
        );
        private $assetsDefaults;
        private $packages;
        
        private $jsdatascriptflag = false;
        
        public function __construct()
        {
            $this->assetsDefaults = require_once EQROOT.'/assets/defaults.php';
            $this->packages = require_once EQROOT.'/assets/packages.php';
        }

        /**
         * @param string $name
         * @param mixed $data
         * @return Client
         */
        public function addJSData($name, $data)
        {
            if(!$this->jsdatascriptflag) {
                $this->addScript("if(!window.EQ) window.EQ = {};\r\nwindow.EQ.data = {};", self::POS_HEAD);
                $this->jsdatascriptflag = true;
            }
            $this->addScript("window.EQ.data.$name = ".json_encode($data).";", self::POS_HEAD);
            return $this;
        }

        /**
         * @param string $code
         * @param string $media
         * @return Client
         */
        public function addCss($code, $media = '')
        {
            $this->css[] = Html::tag('style', array(
                'type' => 'text/css',
                'media' => $media
            ), "\n$code\n");
            return $this;
        }

        /**
         * @param $url
         * @param string $media
         * @return Client
         */
        public function addCssFile($url, $media = '')
        {
            $this->cssFiles[] = Html::tag('link', array(
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => str_replace('//', '/', $url),
                'media' => $media
            ));
            return $this;
        }

        /**
         * @param string $code
         * @param int $position
         * @return Client
         */
        public function addScript($code, $position = self::POS_HEAD)
        {
            if($position == self::POS_READY) {
                $this->scripts[self::POS_READY][] = $code;
                return $this;
            }
            $this->scripts[$position][] = Html::tag(
                'script', 
                array('type' => 'text/javascript'),
                "\n$code\n"
            );
            return $this;
        }

        /**
         * @param string $url
         * @param int $position
         * @return Client
         */
        public function addScriptFile($url, $position = self::POS_HEAD)
        {
            $url = str_replace('//', '/', $url);
            if($position == self::POS_READY) {
                $file = \EQ::app()->content_root.$url;
                if(!file_exists($file))
                    throw new ClientException("Script file not found in content root: $url");
                $this->scripts[self::POS_READY][] = file_get_contents($file);
                return $this;
            }
            $this->scriptFiles[$position][] = Html::tag(
                'script',
                array('type' => 'text/javascript', 'src' => $url),
                ''
            );
            return $this;
        }

        /**
         * @param string    $path
         */
        public function addPageCss($path = null)
        {
            if($path === null)
                $path = \EQ::app()->controller_name.'/'.\EQ::app()->action_name;
            $path = "/css/pages/$path.css";
            $this->addCssFile($path);
            return $this;
        }

        public function addPageScript($path = null, $position = self::POS_READY)
        {
            if($path === null)
                $path = \EQ::app()->controller_name.'/'.\EQ::app()->action_name;
            $path = "/js/pages/$path.js";
            $this->addScriptFile($path, $position);
            return $this;
        }

        /**
         * @param string $name
         * @param string $version
         */
        public function addCoreScript($name, $version = null, $path = 'js', $reload = false, $position = self::POS_HEAD)
        {
            if(isset($this->coreScripts[$name])) return $this;
            $version or $version = $this->getCoreAssetDefault('js', $name);
            $srcpath = EQROOT.'/assets/js/'.str_replace('.', '/', $name)."/$version.js";
            $dstpath = \EQ::app()->content_root."/assets/$path/$name-$version.js";
            if(!is_dir(\EQ::app()->content_root."/assets/$path"))
                mkdir(\EQ::app()->content_root."/assets/$path", 0777, true);
            if(!file_exists($dstpath) || $reload)
                copy($srcpath, $dstpath);
            $this->addScriptFile("/assets/$path/$name-$version.js", $position);
            $this->coreScripts[$name] = $version;
            return $this;
        }

        /**
         * @param string $name
         * @param string $version
         */
        public function addCoreCss($name, $version = null, $path = 'css', $reload = false)
        {
            if(isset($this->coreStyles[$name])) return $this;
            $version or $version = $this->getCoreAssetDefault('css', $name);
            $srcpath = EQROOT.'/assets/css/'.str_replace('.', '/', $name)."/$version.css";
            $dstpath = \EQ::app()->content_root."/assets/$path/$name-$version.css";
            if(!is_dir(\EQ::app()->content_root."/assets/$path"))
                mkdir(\EQ::app()->content_root."/assets/$path", 0777, true);
            if(!file_exists($dstpath) || $reload)
                copy($srcpath, $dstpath);
            $this->addCssFile("/assets/$path/$name-$version.css");
            $this->coreStyles[$name] = $version;
            return $this;
        }

        /**
         * @param string $name
         * @param string $path
         */
        public function addCoreImgs($name, $path = 'img', $reload = false)
        {
            if(isset($this->coreImages[$name])) return $this;
            $srcpath = EQROOT.'/assets/img/'.str_replace('.', '/', $name);
            $dstpath = \EQ::app()->content_root."/assets/$path";
            if(!is_dir($dstpath) || $reload)
                \eq\misc\rcopy($srcpath, $dstpath);
            return $this;
        }

        /**
         * @param string $name
         * @return Client
         */
        public function addPackage($name, $reload = false)
        {
            $pkg = $this->packages[$name];
            $path = $pkg['path'];
            if(!is_dir(\EQ::app()->content_root."/assets/pkgs/$path"))
                mkdir(\EQ::app()->content_root."/assets/pkgs/$path", 0777, true);
            foreach($pkg['css'] as $css => $dir)
                $this->addCoreCss($css, null, "pkgs/$path/$dir", $reload);
            foreach($pkg['js'] as $js => $script) {
                $dir = $script[0];
                $this->addCoreScript($js, null, "pkgs/$path/$dir", $reload, $script[1]);
            }
            foreach($pkg['img'] as $img => $dir)
                $this->addCoreImgs($img, "pkgs/$path/$dir", $reload);
            return $this;
        }

        /**
         * @param string $code
         * @param int $pos
         * @return Client
         */
        public function addHtml($code, $pos = self::POS_END)
        {
            if($pos != self::POS_BEGIN && $pos != self::POS_END)
                throw new ClientException("Wrong html insert position: $pos");
            $this->htmlIns[$pos][] = $code;
            return $this;
        }

        /**
         * @param string $rel
         * @param string $type
         * @param string $href
         * @param string $media
         * @param array $options
         * @return Client
         */
        public function addLinkTag($rel = null, $type = null, $href = null, $media = null, $options = array())
        {
            $attrs = array(
                'rel' => $rel,
                'type' => $type,
                'href' => $href,
                'media' => $media,
            );
            $attrs = array_merge($attrs, $options);
            $this->linkTags[] = Html::tag('link', $attrs);
            return $this;
        }

        /**
         * @param $content
         * @param string $name
         * @param array $options
         * @return Client
         */
        public function addMetaTag($content, $name = null, $options = array())
        {
            $attrs = array_merge(array('name' => $name), $options, array('content' => $content));
            $this->metaTags[] = Html::tag('meta', $attrs);
            return $this;
        }

        /**
         * @param $title
         * @return Client
         */
        public function createTitle($title)
        {
            $this->title = _($title).' — '._(\EQ::app()->config['info']['name']);
            return $this;
        }
        
        public function renderHead()
        {
            $this->title or $this->createDefaultTitle();
            if($this->scripts[self::POS_READY]) {
                $ready_script = '$(function() {'."\n".
                    implode("\n", $this->scripts[self::POS_READY])."\n});";
                $this->addScript($ready_script, self::POS_END);
            }
            $html = array();
            $html[] = Html::tag('title', array(), $this->title);
            $html[] = $this->implodeTags($this->linkTags);
            $html[] = $this->implodeTags($this->metaTags);
            $html[] = $this->implodeTags($this->css);
            $html[] = $this->implodeTags($this->cssFiles);
            $html[] = $this->implodeTags($this->scripts[self::POS_HEAD]);
            $html[] = $this->implodeTags($this->scriptFiles[self::POS_HEAD]);
            return $this->implodeTags($html, true);
        }
        
        public function renderBegin()
        {
            $html = array();
            $html[] = $this->implodeTags($this->scripts[self::POS_BEGIN]);
            $html[] = $this->implodeTags($this->scriptFiles[self::POS_BEGIN]);
            $html[] = $this->implodeTags($this->htmlIns[self::POS_BEGIN]);
            return $this->implodeTags($html, true);
        }
        
        public function renderEnd()
        {
            $html = array();
            $html[] = $this->implodeTags($this->scripts[self::POS_END]);
            $html[] = $this->implodeTags($this->scriptFiles[self::POS_END]);
            $html[] = $this->implodeTags($this->htmlIns[self::POS_END]);
            return $this->implodeTags($html, true);
        }
        
        private function createDefaultTitle()
        {
            $this->title = _(\EQ::app()->config['info']['name']).' — '.
                _(\EQ::app()->config['info']['description']);
        }
        
        private function implodeTags($tags, $ret = false)
        {
            $tags = array_diff($tags, array(''));
            if(empty($tags)) return '';
            $tags = implode("\n", $tags);
            return $ret ? $tags."\n" : $tags;
        }
        
        private function getCoreAssetDefault($type, $name)
        {
            return $this->assetsDefaults[$type][$name];
        }

    }

