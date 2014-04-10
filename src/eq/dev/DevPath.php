<?php

namespace eq\dev;

use EQ;

class DevPath
{

    public static function createProjectFilePath($file)
    {
        if(!\EQ::app()->config("dev.project_root"))
            return $file;
        // return preg_replace(
            // '/^'.\eq\misc\escapeRegExp(\EQ::app()->config['dev']['project_root']).'\//',
            // '', $file
        // );
        return preg_replace(
            '/^'.preg_quote(\EQ::app()->config("dev.project_root"), "/").'\//',
            '', $file
        );
    }

    public static function createIdeLink($file, $line)
    {
        $config = EQ::app()->config('dev');
        $function = EQ::app()->config("dev.create_ide_link_function");
        $protocol = EQ::app()->config("dev.ide_protocol", "vim");
        if(is_callable($function))
            return call_user_func($function, $file, $line);
        if(!EQ::app()->config("dev.project_name"))
            return $protocol."://".$file;
        $fpath = self::createProjectFilePath($file);
        return $protocol."://"
            .$fpath.':'.$line.'@'.EQ::app()->config("dev.project_name");
    }

}
