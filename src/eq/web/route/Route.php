<?php
/**
 * Last Change: 2014 Apr 22, 22:38
 */

namespace eq\web\route;

use EQ;
use eq\helpers\FileSystem;

class Route
{

    use \eq\base\TObject;

    protected $files = [];

    protected $fcache_file = "@runtime/route.files";
    protected $rcache_file = "@runtime/route-cache.json";
    protected $modified = false;

    protected $rules = [];

    public function __construct($files)
    {
        foreach($files as $fname => $fdata) {
            $fname = EQ::getAlias($fname);
            $url_prefix = isset($fdata[0]) ? $fdata[0] : "";
            $path_prefix = isset($fdata[1]) ? $fdata[1] : "";
            $this->files[$fname] = [
                $url_prefix,
                $path_prefix,
                filemtime($fname),
            ];
        }
        if($this->isModified()) {
            $this->loadFiles();
            $this->cacheSave();
            $this->fcacheSave();
        }
        else {
            $this->cacheLoad();
        }
    }

    protected function isModified()
    {
        return true; // DEV!
        if(!FileSystem::isFile($this->fcache_file))
            return true;
        $lines = FileSystem::fgets($this->fcache_file, true);
        $files = $this->files;
        $fname_ = null;
        foreach($lines as $line) {
            if(!is_null($fname_)) {
                $fname = $fname_;
                $fname_ = null;
                if(!isset($files[$fname]))
                    return true;
                if($files[$fname][2] !== (int) $line)
                    return true;
                unset($files[$fname]);
            }
            else
                $fname_ = $line;
        }
        return $files ? true : false;
    }

    protected function fcacheSave()
    {
        $lines = [];
        foreach($this->files as $fname => $fdata) {
            $lines[] = $fname;
            $lines[] = $fdata[2];
        }
        FileSystem::fputs($this->fcache_file, $lines);
    }

    protected function loadFiles()
    {
        foreach($this->files as $fname => $fdata) {
            $file = new RouteFile($fname, $fdata[0], $fdata[1]);
            $this->rules = array_merge($this->rules, $file->rules);
        }
    }

    protected function cacheLoad()
    {
        $cache = json_decode(FileSystem::fgets($this->rcache_file));
        foreach($cache as $data) {
            $rule = new RouteRule();
            $rule->loadData($data);
            $this->rules[] = $rule;
        }
    }

    protected function cacheSave()
    {
        $cache = [];
        foreach($this->rules as $rule)
            $cache[] = $rule->saveData();
        FileSystem::fputs($this->rcache_file, json_encode($cache));
    }

}
