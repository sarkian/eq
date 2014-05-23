<?php

namespace eq\modules\i18n;

use EQ;
use eq\base\ModuleBase;
use eq\helpers\Arr;

class I18nModule extends ModuleBase
{

    protected $enabled_locales;
    protected $default_locale;

    protected $dirs = [];
    protected $tokens = [];
    protected $keys = [];

    public function init()
    {
        $this->enabled_locales = $this->config("enabled_locales", ["en_US"]);
        $this->default_locale = $this->config("default_locale", "en_US");
        $dirs = $this->config("dirs", ["@app/locale" => EQ::app()->app_namespace]);
        foreach($dirs as $dir => $key_prefix)
            $this->addDir($dir, $key_prefix);
        $this->registerStaticMethod("t", [$this, "t"]);
        $this->registerStaticMethod("k", [$this, "k"]);
    }

    public function webInit()
    {
        $locale = EQ::app()->cookie->_locale;
        if(!$this->localeEnabled($locale)) {
            $locale = $this->default_locale;
            EQ::app()->cookie->_locale = $locale;
        }
        EQ::app()->setLocale($locale);
    }
    
    public function consoleInit()
    {
        $locale = $this->default_locale;
        EQ::app()->setLocale($locale);
    }

    public function addDir($dir, $key_prefix = "")
    {
        if(!is_array($this->dirs))
            throw new I18nException(
                "Directories must be added in i18n.beforeLoadFiles callback");
        if(is_int($dir)) {
            $dir = $key_prefix;
            $key_prefix = "";
        }
        $dir = realpath(EQ::getAlias($dir));
        if($dir && !isset($this->dirs[$dir]))
            $this->dirs[$dir] = $key_prefix;
    }

    public function localeEnabled($locale)
    {
        if(!$locale)
            return false;
        return in_array($locale, $this->enabled_locales);
    }

    public function t($text)
    {
        $this->loadFiles();
        return isset($this->tokens[$text]) ? $this->tokens[$text] : $text;
    }

    public function k($key)
    {
        $this->loadFiles();
        if(!isset($this->keys[$key]))
            throw new I18nException("Undefined key: $key");
        return $this->keys[$key];
    }

    protected function loadFiles()
    {
        if(!is_array($this->dirs))
            return;
        $this->trigger("beforeLoadFiles", [$this]);
        $tokens = [];
        $keys = [];
        foreach($this->dirs as $dir => $key_prefix) {
            $files = array_filter(
                glob("$dir/".EQ::app()->locale.".php"), "is_file");
            if($key_prefix)
                $key_prefix = trim($key_prefix, " \r\n\t./");
            foreach($files as $file) {
                $data = require $file;
                if(isset($data['tokens']) && is_array($data['tokens']))
                    $tokens[] = $data['tokens'];
                if(isset($data['keys']) && is_array($data['keys'])) {
                    if($key_prefix) {
                        $keys_ = [];
                        foreach($data['keys'] as $i => $k)
                            $keys_["$key_prefix.$i"] = $k;
                    }
                    else
                        $keys_ = $data['keys'];
                    $keys[] = $keys_;
                }
            }
        }
        if($tokens)
            $this->tokens = call_user_func_array("array_merge", $tokens);
        if($keys)
            $this->keys = call_user_func_array("array_merge", $keys);
        $this->dirs = null;
    }

}
