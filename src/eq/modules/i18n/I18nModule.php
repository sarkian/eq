<?php

namespace eq\modules\i18n;

use EQ;
use eq\base\ModuleBase;
use eq\php\PhpExceptionBase;
use eq\web\Jsdata;

class I18nModule extends ModuleBase
{

    protected $enabled_locales;
    protected $default_locale;

    protected $dirs = [];
    protected $tokens = [];
    protected $keys = [];
    protected $js = [];

    public function init()
    {
        $this->enabled_locales = $this->config("enabled_locales", ["en_US"]);
        $this->default_locale = $this->config("default_locale", "en_US");
        $this->addDir($this->location."/locale", "eq");
        $dirs = $this->config("dirs", ["@app/locale" => EQ::app()->app_namespace]);
        foreach($dirs as $dir => $key_prefix)
            $this->addDir($dir, $key_prefix);
    }

    public function getStaticMethods()
    {
        return [
            't' => [$this, "t"],
            'k' => [$this, "k"],
        ];
    }

    public function webInit()
    {
        $locale = EQ::app()->cookie->_locale;
        if(!$this->localeEnabled($locale)) {
            $locale = $this->default_locale;
            EQ::app()->cookie("_locale", $locale, ['httponly' => false]);
        }
        EQ::app()->setLocale($locale);
        EQ::app()->bind("jsdata.register", function(Jsdata $jsdata) {
            if($this->js)
                $jsdata->set("i18n", $this->js);
        });
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
        $args = func_get_args();
        array_shift($args);
        array_unshift($args, $this->keys[$key]);
        // TODO: запилить нормальную обработку ошибки
        try {
            return call_user_func_array("sprintf", $args);
        }
        catch(PhpExceptionBase $e) {
            return "";
        }
    }

    protected function loadFiles()
    {
        if(!is_array($this->dirs))
            return;
        $this->trigger("beforeLoadFiles", [$this]);
        $tokens = [];
        $keys = [];
        $js = [];
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
                if(isset($data['js']) && is_array($data['js']))
                    $js[] = $data['js'];
            }
        }
        if($tokens)
            $this->tokens = call_user_func_array("array_merge", $tokens);
        if($keys)
            $this->keys = call_user_func_array("array_merge", $keys);
        if($js)
            $this->js = call_user_func_array("array_merge", $js);
        $this->dirs = null;
    }

}
