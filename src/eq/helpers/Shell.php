<?php

namespace eq\helpers;

use eq\base\ShellExecException;
use eq\base\ShellSyntaxException;

class Shell
{

    public static function exec($cmd, $input = null, &$ret = null)
    {
        $thr = func_num_args() < 3;
        $descspec = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"],
        ];
        $proc = proc_open($cmd, $descspec, $pipes);
        if(!is_resource($proc)) {
            if($thr)
                throw new ShellExecException("Cant open process: $cmd");
            else
                return false;
        }
        if(!is_null($input))
            fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = trim(stream_get_contents($pipes[2]), " \r\n\t");
        fclose($pipes[2]);
        $ret = proc_close($proc);
        if($thr && $ret !== 0)
            throw new ShellExecException("Shell returned $ret: $cmd: $err", $ret);
        return $out;
    }

    public static function suexec($cmd, $user, $password
                , $use_file = true, &$err = null,  &$ret = null)
    {
        $thr = func_num_args() < 5;
        $outfile = null;
        $errfile = null;
        if($use_file) {
            $outfile = FileSystem::tempfile(null, 0666);
            $errfile = FileSystem::tempfile(null, 0666);
            $cmd .= " > $outfile 2>$errfile";
        }
        $command = "su ".self::escapeArg($user)." -c ".self::escapeArg($cmd);
        $descspec = [
            0 => ["pty", "r"],
            1 => ["pty", "w"],
            2 => ["pty", "w"],
        ];
        $proc = proc_open($command, $descspec, $pipes);
        if(!is_resource($proc)) {
            if($thr)
                throw new ShellExecException("Cant open process: $cmd");
            else
                return false;
        }
        sleep(1);
        fwrite($pipes[0], $password."\r\n");
//        sleep(1);
        fclose($pipes[0]);
        $out = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $err = trim(stream_get_contents($pipes[2]), " \r\n\t");
        fclose($pipes[2]);
        $ret = proc_close($proc);
        if($outfile && $errfile) {
            $out = FileSystem::fgets($outfile);
            $err = FileSystem::fgets($errfile);
            FileSystem::rm([$outfile, $errfile]);
        }
        if($thr && $ret !== 0)
            throw new ShellExecException("Shell returned $ret: $cmd: $err", $ret);
        return $out;
    }

    public static function split($command, $nothrow = false, &$comment = "")
    {
        if($command === '""' || $command === "''")
            return [""];
        $chars = str_split($command);
        $args = [];
        $arg = "";
        $f_dquote = false;
        $f_squote = false;
        $f_escape = false;
        $f_comment = false;
        foreach($chars as $ch) {
            if($f_comment) {
                $comment .= $ch;
            }
            elseif($ch === "\\") {
                if($f_escape)
                    $arg .= $ch;
                $f_escape = !$f_escape;
            }
            elseif($ch === '"') {
                if($f_escape) {
                    $arg .= $ch;
                    $f_escape = false;
                }
                else
                    $f_dquote = !$f_dquote;
            }
            elseif($ch === "'") {
                if($f_escape) {
                    $arg .= $ch;
                    $f_escape = false;
                }
                else
                    $f_squote = !$f_squote;
            }
            elseif($ch === " ") {
                if($f_escape) {
                    if($f_dquote || $f_squote)
                        $arg .= "\\";
                    $arg .= $ch;
                    $f_escape = false;
                }
                elseif($f_dquote || $f_squote) {
                    $arg .= $ch;
                }
                elseif(strlen($arg)) {
                    $args[] = $arg;
                    $arg = "";
                }
            }
            elseif($ch === "#") {
                if($f_escape) {
                    $arg .= $ch;
                    $f_escape = false;
                }
                elseif($f_dquote || $f_squote) {
                    $arg .= $ch;
                }
                else
                    $f_comment = true;
            }
            else {
                if($f_escape) {
                    if(!in_array($ch, self::specialchars()))
                        $arg .= "\\";
                    $f_escape = false;
                }
                $arg .= $ch;
            }
        }
        if(!$nothrow && ($f_dquote || $f_squote))
            throw new ShellSyntaxException("Unterminated quoted string: ".$command);
        if($f_escape)
            $arg .= "\\";
        if(strlen($arg))
            $args[] = $arg;
        $comment = trim($comment, " \r\n\t");
        return $args;
    }

    public static function escapeArg($arg)
    {
        if(!strlen($arg))
            return '""';
        $wrap = "";
        $charlist = implode("", Shell::specialchars());
        if(strpbrk($arg, $charlist." \"'")) {
            $dq = strpbrk($arg, '"');
            $sq = strpbrk($arg, "'");
            if(!$dq && $sq)
                $wrap = '"';
            elseif($dq && !$sq)
                $wrap = "'";
            else {
                $wrap = '"';
                $arg = str_replace('"', '\\"', $arg);
            }
            $arg = str_replace("\\", "\\\\", $arg);
        }
        return $wrap.$arg.$wrap;
    }

    public static function specialchars()
    {
        return [
            "\\",
            "#",
            "&",
            ";",
            "`",
            "|",
            "*",
            "?",
            "~",
            "<",
            ">",
            "^",
            "(",
            ")",
            "[",
            "]",
            "{",
            "}",
            "$",
            "\x0A",
            "\x0F",
        ];
    }

}
