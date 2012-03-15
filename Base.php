<?php

// +----------------------------------------------------------------------
// | Buddy Framework
// +----------------------------------------------------------------------
// | Copyright (c) 2011 http://buddy.woshimaijia.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: xinqiyang <xinqiyang@gmail.com>
// +----------------------------------------------------------------------

/**
 * Base class
 * buddy's base 
 * all of the class should extend it
 * @author xinqiyang
 *
 */
class Base {

    private static $_instance = array();

    public function __set($name, $value) {
        if (property_exists($this, $name)) {
            $this->$name = $value;
        }
    }

    public function __get($name) {
        return isset($this->$name) ? $this->$name : null;
    }

    /**
     * get the instance of the class
     * @param string $class class name
     * @param string $parmaNode param node of the class
     * @param string $method method name
     */
    public static function instance($class = '', $paramNode = '', $method = '') {
        $identify = $class . $paramNode . $method;
        if (!isset(self::$_instance[$identify])) {
            if (class_exists($class)) {
                $o = empty($paramNode) ? new $class() : new $class(strtolower($class) . '.' . strtolower($paramNode));
                if (!empty($method) && method_exists($o, $method)) {
                    self::$_instance[$identify] = call_user_func_array(array(&$o, $method));
                } else {
                    self::$_instance[$identify] = $o;
                }
            } else {
                logNotice(__CLASS__ . '/' . __FUNCTION__ . ":$class CLASS_NOT_EXIST ");
                exit();
            }
        }
        return self::$_instance[$identify];
    }

    /**
     * auto load file
     * auto load file from path
     * @param string $classname class name 
     */
    public static function autoload($classname) {
        $type = substr($classname, -5);
        if ($type == "ction") {
            require_cache(ACTION_PATH . DIRECTORY_SEPARATOR . $classname . '.php');
        } elseif ($type == "rvice") {
            require_cache(SERVICE_PATH . DIRECTORY_SEPARATOR . $classname . '.php');
        } elseif ($type == 'Logic') { 
            require_cache(LOGIC_PATH . DIRECTORY_SEPARATOR . $classname . '.php');
        } else {
            if (C('app_autoload_path')) {
                $paths = explode(',', C('app_autoload_path'));
                foreach ($paths as $path) {
                    if (require_cache($path . DIRECTORY_SEPARATOR . $classname . '.php')) {
                        return;
                    }
                }
            }
            return;
        }
    }

}