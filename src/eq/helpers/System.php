<?php

namespace eq\helpers;

class System
{

    public static function procGetAll()
    {
        $processes = [];
        exec("ps aux", $lines);
        foreach($lines as $line) {
            $words = explode(" ", $line);
            $words = array_merge(array_diff($words, [""]));
            $pid = (int) $words[1];
            if($pid)
                $processes[$pid] = implode(" ", array_slice($words, 10));
        }
        return $processes;
    }

    public static function procGetArgs($pid)
    {
        $fname = Path::join(["/proc", $pid, "cmdline"]);
        if(!file_exists($fname))
            return false;
        $line = FileSystem::fgets($fname);
        $args = explode("\0", $line);
        if($args[count($args) - 1] === "")
            array_pop($args);
        return $args;
    }

}
