<?php
/**
 * Last Change: 2014 Apr 09, 00:22
 */

namespace eq\helpers;

use EQ;
use eq\base\FileSystemException;

class FileSystem
{

    public static function assertExists($path)
    {
        $path = EQ::getAlias($path);
        if(!file_exists($path))
            throw new FileSystemException("File not found: ".$path);
    }

    public static function assertFile($path)
    {
        $path = EQ::getAlias($path);
        if(!file_exists($path) || !is_file($path))
            throw new FileSystemException("File not found: $path");
    }

    public static function assertWritable($path)
    {
        $path = EQ::getAlias($path);
        self::assertExists($path);
        if(!is_writable($path))
            throw new FileSystemException(
                (is_dir($path) ? "Directory" : "File")
                ." is not writable: ".$path
            );
    }

    public static function mkdir($path, $mode = 0775, $recursive = true)
    {
        umask(0);
        $path = EQ::getAlias($path);
        if(is_dir($path)) {
            if(fileperms($path) !== $mode)
                @chmod($path, $mode);
            return;
        }
        if(!mkdir($path, $mode, $recursive))
            throw new FileSystemException("Unable to create directory: ".$path);
    }

    public static function rm($path, $nothrow = false)
    {
        $path = EQ::getAlias($path);
        if(is_dir($path))
            self::rmdir($path, $nothrow);
        elseif(file_exists($path))
            self::rmfile($path, $nothrow);
    }

    public static function fputs($fname, $data, $append = false)
    {
        $fname = EQ::getAlias($fname);
        $flags = $append ? FILE_APPEND : 0;
        self::mkdir(dirname($fname));
        if(is_array($data))
            $data = implode("\n", $data);
        if(@file_put_contents($fname, $data, $flags) === false)
            throw new FileSystemException("Unable to write file: ".$fname);
    }

    public static function fgets($fname, $as_array = false)
    {
        $fname = EQ::getAlias($fname);
        $data = @file_get_contents($fname);
        if($data === false)
            throw new FileSystemException("Unable to read file: ".$fname);
        return $as_array ? explode("\n", $data) : $data;
    }

    public static function copy($src, $dst, $force = false)
    {
        self::_copy(EQ::getAlias($src), EQ::getAlias($dst), $force);
    }

    protected static function _copy($src, $dst, $force = false)
    {
        self::assertExists($src);
        if(!$force && file_exists($dst) && filetype($src) === filetype($dst))
            return;
        if(is_dir($src)) {
            if(!file_exists($dst))
                self::mkdir($dst);
            $files = scandir($src);
            foreach($files as $file) {
                if($file !== "." && $file !== "..")
                    self::_copy("$src/$file", "$dst/$file");
            }
        }
        else {
            self::mkdir(dirname($dst));
            copy($src, $dst);
        }
    }

    protected static function rmfile($path, $nothrow = false)
    {
        $path = EQ::getAlias($path);
        if(!unlink($path) && !$nothrow)
            throw new FileSystemException("Unable to remove file: ".$path);
    }

    protected static function rmdir($path, $nothrow = false)
    {
        $path = EQ::getAlias($path);
        foreach(scandir($path) as $file) {
            if($file === "." || $file === "..")
                continue;
            $file = Path::join([$path, $file]);
            if(is_file)
                self::rmfile($file, $nothrow);
            else
                self::rmdir($file, $nothrow);
        }
        if(!rmdir($path) && !$nothrow)
            throw new FileSystemException("Unable to remove directory: ".$path);
    }

}
