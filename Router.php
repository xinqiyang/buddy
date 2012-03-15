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
 * Router for the Request
 * use the Router
 * @author xinqiyang
 *
 */
class Router extends Base {

    /**
     * dispath request
     * @TODO need prepair
     * show the friendly url for app
     */
    public static function dispatch() {
        //SET url mode   1 compact 2 rewrite
        $urlMode = C('url_mode');
        if ($urlMode == 2) {
            $url = substr(_PHP_FILE_, 10);
            if ($url == '/' || $url == '\\') {
                $url = '';
            }
            define('PHP_FILE', $url);
        } elseif ($urlMode == 1) {
            define('PHP_FILE', _PHP_FILE_ . '?' . C('var_pathinfo') . '=');
        } else {
            define('PHP_FILE', _PHP_FILE_);
        }


        $depr = '/';
        //check pathinfo;
        self::getPathInfo();
        //
        if (!self::routerCheck()) {
            $paths = explode($depr, trim($_SERVER['PATH_INFO'], '/'));
            $var = array();
            if (!isset($_GET['m'])) {
                $var['m'] = array_shift($paths);
            }
            $var['a'] = array_shift($paths);
            //$res = preg_replace('@(\w+)' . $depr . '([^' . $depr . '\/]+)@e', '$var[\'\\1\']="\\2";', implode($depr, $paths));
            $_GET = array_merge($var, $_GET);
        }
        //set module and action name
        define('MODULE_NAME', self::getModule('m'));
        define('ACTION_NAME', self::getAction('a'));

        define('__SELF__', $_SERVER['REQUEST_URI']);
        define('__INFO__', $_SERVER['PATH_INFO']);
        define('__APP__', '/');
        define('__URL__', __APP__ . MODULE_NAME . '/' . ACTION_NAME);
        define('__ACTION__', __URL__ . $depr . ACTION_NAME);
        $_REQUEST = array_merge($_POST, $_GET);
    }

    /**
     * get pathinfo of request
     */
    public static function getPathInfo() {
        if (!empty($_GET[C('var_pathinfo')])) {
            $path = $_GET[C('var_pathinfo')];
            unset($_GET[C('var_pathinfo')]);
            //var_dump('var_pathinfo',$path);
        } elseif (!empty($_SERVER['PATH_INFO'])) {
            $pathInfo = $_SERVER['PATH_INFO'];
            if (0 === strpos($pathInfo, $_SERVER['SCRIPT_NAME'])) {
                $path = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
            } else {
                $path = $pathInfo;
            }
            //var_dump('PATH_INFO',$path);
        } elseif (!empty($_SERVER['ORIG_PATH_INFO'])) {
            $pathInfo = $_SERVER['ORIG_PATH_INFO'];
            if (0 === strpos($pathInfo, $_SERVER['SCRIPT_NAME'])) {
                $path = substr($pathInfo, strlen($_SERVER['SCRIPT_NAME']));
            } else {
                $path = $pathInfo;
            }
            //var_dump('ORIG_PATH_INFO',$path);
        } elseif (!empty($_SERVER['REDIRECT_PATH_INFO'])) {
            $path = $_SERVER['REDIRECT_PATH_INFO'];
            //var_dump('REDIRECT_PATH_INFO',$path);
        } elseif (!empty($_SERVER["REDIRECT_Url"])) {
            $path = $_SERVER["REDIRECT_Url"];
            $GET = array();
            if (empty($_SERVER['QUERY_STRING']) || $_SERVER['QUERY_STRING'] == $_SERVER["REDIRECT_QUERY_STRING"]) {
                $parsedUrl = parse_url($_SERVER["REQUEST_URI"]);
                if (!empty($parsedUrl['query'])) {
                    $_SERVER['QUERY_STRING'] = $parsedUrl['query'];
                    parse_str($parsedUrl['query'], $GET);
                    $_GET = array_merge($_GET, $GET);
                    reset($_GET);
                } else {
                    unset($_SERVER['QUERY_STRING']);
                }
                reset($_SERVER);
            }
        } else {
            //var_dump('PHP_FILE',PHP_FILE);
            $path = PHP_FILE;
        }

        //TODO: need ???
        if (C('url_html_suffix') && !empty($path)) {
            $path = preg_replace('/\.' . trim(C('url_html_suffix'), '.') . '$/', '', $path);
        }
        //set path_info
        $_SERVER['PATH_INFO'] = empty($path) ? '/' : $path;
        //var_dump($path,$_GET);
    }

    private static function parseUrl($route) {
        $array = explode('/', $route);
        $var = array();
        $var['m'] = array_pop($array);
        $var['a'] = array_pop($array);
        return $var;
    }

    private static function getModule($var) {
        $module = (!empty($_GET[$var]) ? $_GET[$var] : 'index');
        unset($_GET[$var]);
        return $module;
    }

    /**
     * get now action name
     * @param string $var 
     */
    private static function getAction($var) {
        $action = !empty($_POST[$var]) ?
                $_POST[$var] :
                (!empty($_GET[$var]) ? $_GET[$var] : 'index');
        unset($_POST[$var], $_GET[$var]);
        return strtolower($action);
    }

    /**
     * router check
     * check url to do the short url
     */
    public static function routerCheck() {
        $regx = trim($_SERVER['PATH_INFO'], '/');
        if (empty($regx))
            return true;
        $routes = C(APP_NAME . '_route_rules');
        if (!empty($routes)) {
            $depr = '/';
            //TODO:change ,only support one to router
            foreach ($routes as $key => $route) {
                if (1 < substr_count($route[0], '/') && preg_match($route[0], $regx, $matches)) {
                    $url = explode($depr, $regx);
                    //$path = $url[0] . $depr . $route[2] . $depr . $route[3] . $url[1];
                    $var = array('m' => $url[0], 'a' => $route[2], $route[3] => $url[1]);
                    $_GET = array_merge($var, $_GET);
                }
            }
        }
        return false;
    }

}

