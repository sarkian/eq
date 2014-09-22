<?php

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

    public static function exists($path)
    {
        $path = EQ::getAlias($path);
        return file_exists($path);
    }

    public static function isFile($path)
    {
        $path = EQ::getAlias($path);
        return is_file($path);
    }

    public static function isDir($path)
    {
        $path = EQ::getAlias($path);
        return is_file($path);
    }

    public static function mkdir($path, $mode = 0775, $recursive = true)
    {
        umask(0);
        $path = EQ::getAlias($path);
        if(is_link($path))
            $path = realpath($path);
        if(is_dir($path)) {
            if(fileperms($path) !== $mode)
                @chmod($path, $mode);
            return;
        }
        if(!@mkdir($path, $mode, $recursive))
            throw new FileSystemException("Unable to create directory: ".$path);
    }

    public static function rm($path, $nothrow = false)
    {
        if(!is_array($path))
            $path = [$path];
        foreach($path as $p) {
            $p = EQ::getAlias($p);
            if(is_dir($p))
                self::rmdir($p, $nothrow);
            elseif(file_exists($p))
                self::rmfile($p, $nothrow);
        }
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

    public static function filemtime($fname)
    {
        $fname = EQ::getAlias($fname);
        return filemtime($fname);
    }

    public static function tempfile($dir = null, $mode = 0600)
    {
        if($dir)
            $dir = EQ::getAlias($dir);
        is_dir($dir) or $dir = sys_get_temp_dir();
        $fname = tempnam($dir, "eqtmp");
        if($mode !== 0600) {
            umask(0);
            chmod($fname, $mode);
        }
        return $fname;
    }

    public static function glob($pattern, $flags = 0)
    {
        return glob(EQ::getAlias($pattern, $flags));
    }

    public static function concat($input, $output, $nl = false)
    {
        is_array($input) or $input = [$input];
        $outfile = @fopen(EQ::getAlias($output), "w");
        if($outfile === false)
            throw new FileSystemException("Cant open file for writing: $output");
        foreach($input as $fname)
            fwrite($outfile, static::fgets($fname).($nl ? "\n" : ""));
        fclose($outfile);
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
                    self::_copy("$src/$file", "$dst/$file", $force);
            }
        } else {
            self::mkdir(dirname($dst));
            copy($src, $dst);
        }
    }

    protected static function rmfile($path, $nothrow = false)
    {
        $path = EQ::getAlias($path);
        if(!@unlink($path) && !$nothrow)
            throw new FileSystemException("Unable to remove file: ".$path);
    }

    protected static function rmdir($path, $nothrow = false)
    {
        $path = EQ::getAlias($path);
        foreach(scandir($path) as $file) {
            if($file === "." || $file === "..")
                continue;
            $file = Path::join([$path, $file]);
            if(is_file($file))
                self::rmfile($file, $nothrow);
            else
                self::rmdir($file, $nothrow);
        }
        if(!@rmdir($path) && !$nothrow)
            throw new FileSystemException("Unable to remove directory: ".$path);
    }

}
