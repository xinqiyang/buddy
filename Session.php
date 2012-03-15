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

define("HTTP_SESSION_STARTED",      1);
define("HTTP_SESSION_CONTINUED",    2);

/**
 * Session controller 
 * @author xinqiyang
 *
 */
class Session extends Base
{


    static function start()
    {
        session_start();
        if (!isset($_SESSION['__HTTP_Session_Info'])) {
            $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_STARTED;
        } else {
            $_SESSION['__HTTP_Session_Info'] = HTTP_SESSION_CONTINUED;
        }
        Session::setExpire(C('SESSION_EXPIRE'));
    }

 
    static function pause()
    {
        session_write_close();
    }


    static function clearLocal()
    {
        $local = Session::localName();
        unset($_SESSION[$local]);
    }

   
    static function clear()
    {
        $_SESSION = array();
    }

    
    static function destroy()
    {
        unset($_SESSION);
        session_destroy();
    }

    
    static function detectID()
    {
        if(session_id()!='')
        {
            return session_id();
        }
        if (Session::useCookies()) {
            if (isset($_COOKIE[Session::name()])) {
                return $_COOKIE[Session::name()];
            }
        } else {
            if (isset($_GET[Session::name()])) {
                return $_GET[Session::name()];
            }
            if (isset($_POST[Session::name()])) {
                return $_POST[Session::name()];
            }
        }
        return null;
    }

   
    static function name($name = null)
    {
        return isset($name) ? session_name($name) : session_name();
    }

    
    static function id($id = null)
    {
        return isset($id) ? session_id($id) : session_id();
    }

    
    static function path($path = null)
    {
        return !empty($path)? session_save_path($path):session_save_path();
    }

    
    static function setExpire($time, $add = false)
    {
        if ($add) {
            if (!isset($_SESSION['__HTTP_Session_Expire_TS'])) {
                $_SESSION['__HTTP_Session_Expire_TS'] = time() + $time;
            }

            // update session.gc_maxlifetime
            $currentGcMaxLifetime = Session::setGcMaxLifetime(null);
            Session::setGcMaxLifetime($currentGcMaxLifetime + $time);

        } elseif (!isset($_SESSION['__HTTP_Session_Expire_TS'])) {
                $_SESSION['__HTTP_Session_Expire_TS'] = $time;
        }
    }

   
    static function setIdle($time, $add = false)
    {
        if ($add) {
            $_SESSION['__HTTP_Session_Idle'] = $time;
        } else {
            $_SESSION['__HTTP_Session_Idle'] = $time - time();
        }
    }

    
    static function sessionValidThru()
    {
        if (!isset($_SESSION['__HTTP_Session_Idle_TS']) || !isset($_SESSION['__HTTP_Session_Idle'])) {
            return 0;
        } else {
            return $_SESSION['__HTTP_Session_Idle_TS'] + $_SESSION['__HTTP_Session_Idle'];
        }
    }

   
    static function isExpired()
    {
        if (isset($_SESSION['__HTTP_Session_Expire_TS']) && $_SESSION['__HTTP_Session_Expire_TS'] < time()) {
            return true;
        } else {
            return false;
        }
    }

    
    static function isIdle()
    {
        if (isset($_SESSION['__HTTP_Session_Idle_TS']) && (($_SESSION['__HTTP_Session_Idle_TS'] + $_SESSION['__HTTP_Session_Idle']) < time())) {
            return true;
        } else {
            return false;
        }
    }

    
    static function updateIdle()
    {
        $_SESSION['__HTTP_Session_Idle_TS'] = time();
    }

    
    static function setCallback($callback = null)
    {
        $return = ini_get('unserialize_callback_func');
        if (!empty($callback)) {
            ini_set('unserialize_callback_func',$callback);
        }
        return $return;
    }

    
    static function useCookies($useCookies = null)
    {
        $return = ini_get('session.use_cookies') ? true : false;
        if (isset($useCookies)) {
            ini_set('session.use_cookies', $useCookies ? 1 : 0);
        }
        return $return;
    }

    
    static function isNew()
    {
        return !isset($_SESSION['__HTTP_Session_Info']) ||
            $_SESSION['__HTTP_Session_Info'] == HTTP_SESSION_STARTED;
    }


    
    static function getLocal($name)
    {
        $local = Session::localName();
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        return $_SESSION[$local][$name];
    }

   
    static function get($name)
    {
        if(isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }else {
            return null;
        }
    }

    
    public static function setLocal($name, $value)
    {
        $local = Session::localName();
        if (!is_array($_SESSION[$local])) {
            $_SESSION[$local] = array();
        }
        if (null === $value) {
            unset($_SESSION[$local][$name]);
        } else {
            $_SESSION[$local][$name] = $value;
        }
        return;
    }

    
    public static function set($name, $value)
    {
        if (null === $value) {
            unset($_SESSION[$name]);
        } else {
            $_SESSION[$name] = $value;
        }
        return ;
    }

    
    static function is_setLocal($name)
    {
        $local = Session::localName();
        return isset($_SESSION[$local][$name]);
    }

    
    static function is_set($name)
    {
        return isset($_SESSION[$name]);
    }

    
    static function localName($name = null)
    {
        $return = (isset($GLOBALS['__HTTP_Session_Localname'])) ? $GLOBALS['__HTTP_Session_Localname'] : null;
        if (!empty($name)) {
            $GLOBALS['__HTTP_Session_Localname'] = md5($name);
        }
        return $return;
    }

    
    static function _init()
    {
        ini_set('session.auto_start', 0);
        if (is_null(Session::detectID())) {
            Session::id(uniqid(dechex(mt_rand())));
        }
        // 设置Session有效域名
        Session::setCookieDomain(C('COOKIE_DOMAIN'));
        //设置当前项目运行脚本作为Session本地名
        Session::localName(APP_NAME);
        Session::name(C('SESSION_NAME'));
        Session::path(C('SESSION_PATH'));
        Session::setCallback(C('SESSION_CALLBACK'));
    }

    
    static function useTransSID($useTransSID = null)
    {
        $return = ini_get('session.use_trans_sid') ? true : false;
        if (isset($useTransSID)) {
            ini_set('session.use_trans_sid', $useTransSID ? 1 : 0);
        }
        return $return;
    }

    
    static function setCookieDomain($sessionDomain = null)
    {
        $return = ini_get('session.cookie_domain');
        if(!empty($sessionDomain)) {
            ini_set('session.cookie_domain', $sessionDomain);//跨域访问Session
        }
        return $return;
    }


   
    static function setGcMaxLifetime($gcMaxLifetime = null)
    {
        $return = ini_get('session.gc_maxlifetime');
        if (isset($gcMaxLifetime) && is_int($gcMaxLifetime) && $gcMaxLifetime >= 1) {
            ini_set('session.gc_maxlifetime', $gcMaxLifetime);
        }
        return $return;
    }

    
    static function setGcProbability($gcProbability = null)
    {
        $return = ini_get('session.gc_probability');
        if (isset($gcProbability) && is_int($gcProbability) && $gcProbability >= 1 && $gcProbability <= 100) {
            ini_set('session.gc_probability', $gcProbability);
        }
        return $return;
    }

    
    static function getFilename()
    {
        return Session::path().'/sess_'.session_id();
    }

}

Session::_init();