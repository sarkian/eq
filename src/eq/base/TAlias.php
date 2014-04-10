<?php

namespace eq\base;

trait TAlias
{

    protected static $aliases = [];

    public static function setAlias($alias, $path)
    {
        if(strncmp($alias, '@', 1)) {
			$alias = '@' . $alias;
		}
		$pos = strpos($alias, '/');
		$root = $pos === false ? $alias : substr($alias, 0, $pos);
		if ($path !== null) {
			$path = strncmp($path, '@', 1) ? rtrim($path, '\\/') : static::getAlias($path);
			if (!isset(static::$aliases[$root])) {
				if ($pos === false) {
					static::$aliases[$root] = $path;
				} else {
					static::$aliases[$root] = [$alias => $path];
				}
			} elseif (is_string(static::$aliases[$root])) {
				if ($pos === false) {
					static::$aliases[$root] = $path;
				} else {
					static::$aliases[$root] = [
						$alias => $path,
						$root => static::$aliases[$root],
					];
				}
			} else {
				static::$aliases[$root][$alias] = $path;
				krsort(static::$aliases[$root]);
			}
		} elseif (isset(static::$aliases[$root])) {
			if (is_array(static::$aliases[$root])) {
				unset(static::$aliases[$root][$alias]);
			} elseif ($pos === false) {
				unset(static::$aliases[$root]);
			}
		}
    }

    public static function isAlias($alias)
    {
        return !strncmp($alias, "@", 1);
    }

    public static function getAlias($alias, $throw_exception = true)
    {
        if(strncmp($alias, "@", 1))
			return $alias;
		$pos = strpos($alias, "/");
		$root = $pos === false ? $alias : substr($alias, 0, $pos);
		if(isset(static::$aliases[$root])) {
			if(is_string(static::$aliases[$root])) {
				return $pos === false ? static::$aliases[$root] : static::$aliases[$root].substr($alias, $pos);
            }
            else {
				foreach(static::$aliases[$root] as $name => $path) {
					if(strpos($alias."/", $name."/") === 0) {
						return $path.substr($alias, strlen($name));
					}
				}
			}
		}
		if($throw_exception)
			throw new InvalidParamException("Invalid path alias: $alias");
		else
			return false;
    }

    public static function getAliasRoot($alias)
    {
        $pos = strpos($alias, "/");
        $root = $pos === false ? $alias : substr($alias, 0, $pos);
        return isset(static::$aliases[$root]) ? static::$aliases[$root] : false;
    }

}
