<?php

/**
 * Last Change: 2014 Apr 08, 00:59
 */

namespace eq\console;

use eq\base\LoaderException;
use eq\base\console\InvalidOptionException;
use eq\helpers\Arr;

class ConsoleApp extends \eq\base\AppBase
{

    protected $argc;
    protected $argv;
    protected $command_name;
    protected $action_name;

    public function __construct($config)
    {
        parent::$_app = $this;
        parent::__construct($config);
    }

    public function setRoute($command, $action)
    {
        $this->command_name = $command;
        $this->action_name = $action;
    }

    public function getArgc()
    {
        return $this->argc;
    }

    public function getArgv()
    {
        return $this->argv;
    }

    public function run()
    {
        $this->argv = Arr::getItem($_SERVER['argv'], []);
        $this->argc = Arr::getItem($_SERVER['argc'], count($this->argv));
        list($args, $opts) = $this->parseCmd([], [
            'command' => null,
            'action' => null,
        ], [
            'commands' => 'bool',
            'actions' => 'bool',
            'pure-print' => 'bool',
        ], 0, 1);
        if($args['command']) {
            $command = new ReflectionCommand($args['command']);
            if(!$command->exists()) {
                if(!$opts['pure-print'])
                    echo "Unknown command: {$args['command']}\n";
                return -1;
            }
            if($args['action']) {
                $action = $command->getAction($args['action']);
                if(!$action) {
                    if(!$opts['pure-print'])
                        echo "Unknown action: {$args['action']}\n";
                    return -1;
                }
                $this->setRoute($args['command'], $args['action']);
                return $action->run();
            }
            elseif(!$opts['actions'])
                return $command->getDefaultAction()->run();
            else
                Console::printVariants('Available actions:',
                    $command->getActions(!$opts['pure-print']), $opts['pure-print']);
        }
        elseif($opts['commands'])
            Console::printVariants('Available commands:',
                ReflectionCommand::getCommands(), $opts['pure-print']);
        else
            $this->printUsage();
    }

    public function processFatalError($err)
    {
        // TODO Implement
        echo "Fatal Error:\n";
        print_r($err);
    }

    public function processUncaughtException($e)
    {
        // TODO Implement
        echo get_class($e).": ".$e->getMessage()."\n\n";
        echo $e->getTraceAsString()."\n";
    }

    // TODO Перевести на datatypes и заменить die() на исключение
    public function parseCmd($required, $optional = [], $opts = [], $skip_route = true, $ignore_unknown = false)
    {
        $res = [];
        $argv = $this->argv;
        array_shift($argv);
        if($skip_route) {
            if($argv[0] === $this->command_name)
                array_shift($argv);
            if($this->action_name && $argv && $argv[0] === $this->action_name)
                array_shift($argv);
        }
        list($argv, $opts) = $this->getOpts($argv, $opts, $ignore_unknown);
        if(count($argv) < count($required))
            die("Missing arguments: ".implode(', ', array_slice($required, count($argv)))."\n");
        $arglist = array_merge($required, $optional);
        foreach($required as $arg)
            $res[$arg] = \array_shift($argv);
        foreach($optional as $arg => $default) {
            $value = \array_shift($argv);
            $res[$arg] = \is_null($value) ? $default : $value;
        }
        return array($res, $opts);
    }

    private function getOpts($argv, $opts, $ignore_unknown)
    {
        $argv_res = array();
        $opts_res = array();
        foreach($opts as $opt => $type) {
            switch($type) {
                case 'int':
                    $opts_res[$opt] = 0;
                    break;
                case 'str':
                case 'string':
                    $opts_res[$opt] = '';
                    break;
                case 'bool':
                    $opts_res[$opt] = false;
                    break;
                default:
                    throw new InvalidOptionException("Unknown option type: $type");
            }
        }
        $arg_expect = null;
        foreach($argv as $i => $arg) {
            if($arg_expect) {
                $opts_res[$arg_expect] = $opts[$arg_expect] === 'int' ? (int) $arg : $arg;
                $arg_expect = null;
                continue;
            }
            if(strlen($arg) >= 3 && $arg[0] === '-' && $arg[1] === '-') { // long option
                $arg = substr($arg, 2);
                $arg = explode('=', $arg, 2);
                if(strlen($arg[0]) < 2)
                    die("Invalid option: --{$arg[0]}\n");
                if(!isset($opts[$arg[0]])) {
                    if($ignore_unknown) continue;
                    else die("Unknown option: {$arg[0]}\n");
                }
                if(count($arg) == 2) {
                    if($opts[$arg[0]] === 'bool')
                        die("Option '{$arg[0]} has no value\n");
                    $opts_res[$arg[0]] = $opts[$arg[0]] === 'int' ? (int) $arg[1] : $arg[1];
                }
                else {
                    if($opts[$arg[0]] !== 'bool')
                        die("Missing value for '{$arg[0]}'\n");
                    $opts_res[$arg[0]] = true;
                }
            }
            elseif(strlen($arg) >= 2 && $arg[0] === '-') { // short option
                $arg = substr($arg, 1);
                if(strlen($arg) > 1)
                    die("Invalid option: -$arg\n");
                if(!isset($opts[$arg])) {
                    if($ignore_unknown) continue;
                    else die("Unknown option: $arg\n");
                }
                if($opts[$arg] === 'bool')
                    $opts_res[$arg] = true;
                else {
                    if(!isset($argv[$i + 1]))
                        die("Missing value for '$arg'\n");
                    $arg_expect = $arg;
                }
            }
            else $argv_res[] = $arg;
        }
        return array($argv_res, $opts_res);
    }

    private function printUsage()
    {
        echo "Usage: {$this->argv[0]} <command> <action>\n".
             "Options:\n".
             "    --commands [--pure-print] -- Available commands\n".
             "    --actions <command> [--pure-print]  -- Available actions\n";
    }


}
