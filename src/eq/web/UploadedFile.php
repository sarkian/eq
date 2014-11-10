<?php

namespace eq\web;

use EQ;
use eq\helpers\FileSystem;

/**
 * @property string filename
 */
class UploadedFile
{

    public $name;
    public $type;
    public $tmp_name;
    public $error;
    public $size;

    public $extension = "";

    protected $exists = false;

    public function __construct(array $info)
    {
        if(!empty($info)) {
            $this->name = isset($info['name']) ? $info['name'] : null;
            $this->type = isset($info['type']) ? $info['type'] : "";
            $this->tmp_name = isset($info['tmp_name']) ? $info['tmp_name'] : null;
            $this->error = isset($info['error']) ? $info['error'] : 0;
            $this->size = isset($info['size']) ? $info['size'] : 0;
            if($this->name !== null)
                $this->extension = strtolower(pathinfo($this->name, PATHINFO_EXTENSION));
            $this->exists = true;
        }
    }

    public function success()
    {
        return $this->exists ? !$this->error && is_string($this->name) && strlen($this->name)
            && is_string($this->tmp_name) && strlen($this->tmp_name) && is_file($this->tmp_name)
            && $this->size > 0 : false;
    }

    public function extensionMatch($patterns)
    {
        is_array($patterns) or $patterns = [];
        foreach($patterns as $pattern) {
            if(!fnmatch($pattern, $this->extension))
                return false;
        }
        return true;
    }

    public function typeMatch($patterns)
    {
        is_array($patterns) or $patterns = [];
        foreach($patterns as $pattern) {
            if(!fnmatch($pattern, $this->type))
                return false;
        }
        return true;
    }

    public function getFilename()
    {
        return pathinfo($this->name, PATHINFO_FILENAME);
    }

    public function save($dir, $name = null)
    {
        $name or $name = $this->name;
        $dir = EQ::getAlias($dir);
        FileSystem::mkdir($dir);
        return move_uploaded_file($this->tmp_name, "$dir/$name");
    }

    public function __destruct()
    {
        FileSystem::rm($this->tmp_name);
    }

}