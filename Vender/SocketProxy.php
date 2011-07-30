<?php

/**
 * socket connect to other
 */
class SocketProxy {

    public static $socket = null;

    /**
     * 取得可连接的服务器
     *
     * @param array $arrConfig 主机地址 array('192.168.0.10:8080', '192.168.0.11:8080')
     * @param int $ctime: connect timed out in msec, NULL means no timedout
     * @param int $rtime: read timed out in msec, NULL means no timedout
     * @param int $wtime: write timed out in msec, NULL means no timedout
     * @return false/resource
     */
    public static function socketConnect($arrConfig, $ctime = 1000, $rtime = 5000, $wtime = 5000) {
        if (!is_array($arrConfig)) {
            return false;
        }

        shuffle($arrConfig);
        foreach ($arrConfig as $val) {
            $config = explode(':', $val);
            $address = trim($config[0]);
            $port = intval($config[1]);
            $ret = self::tcpConnect($address, $port, $ctime);
            if ($ret && is_resource($ret)) {
                if ($rtime !== NULL) {
                    socket_set_option($ret, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => $rtime * 1000));
                }

                if ($wtime !== NULL) {
                    socket_set_option($ret, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => $wtime * 1000));
                }

                self::$socket = $ret;
                return true;
            }
        }

        return false;
    }

    /**
     * socket写数据
     *
     * @param string $data
     * @param int $dataLen
     * @return false/$dataLen
     */
    public static function socketWrite($data, $dataLen) {
        if ($ret = socket_write(self::$socket, $data, $dataLen)) {
            return $ret;
        } else {
            self::socketClose();
            return false;
        }
    }

    /**
     * socket读数据
     *
     * @param int $dataLen
     * @return false/$data
     */
    public static function socketRead($dataLen) {
        $dataLen = intval($dataLen);
        if (self::$socket) {
            $tmp = '';
            $data = '';
            while ($tmp = socket_read(self::$socket, $dataLen)) {
                $data .= $tmp;
                $dataLen -= strlen($tmp);
            }

            if (strlen($data) > 0) {
                return $data;
            } else {
                self::socketClose();
                return false;
            }
        } else {
            self::socketClose();
            return false;
        }
    }

    /**
     * socket关闭
     *
     */
    public static function socketClose() {
        if (self::$socket) {
            socket_close(self::$socket);
            self::$socket = null;
        }
    }

    /**
     * 连接到服务器
     *
     * @param string $address 主机地址
     * @param int $port 服务端口
     * @param int $ctimeout 超时限制
     * @return false/
     */
    protected static function tcpConnect($address, $port, $ctimeout = NULL) {
        if (self::$socket) {
            return self::$socket;
        }

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $tv_sec = 0;
        $tv_usec = $ctimeout * 1000;

        if ($ctimeout === NULL) {
            $tv_sec = $tv_usec = NULL;
        }

        if ($socket === false) {
            return false;
        }
	/*
        if (!socket_set_nonblock($socket)) {
            socket_close($socket);
            return false;
        }
	*/
	//var_dump($address,$port);
        //socket描述符设置为无阻塞，瞬间不能连接成功，所以用@忽略警告
        @socket_connect($socket, $address, $port);
        /*
	if (!socket_set_block($socket)) {
            socket_close($socket);
            return false;
        }
	*/
        $r = array($socket);
        $w = array($socket);
        $ret = socket_select($r, $w, $e, $tv_sec, $tv_usec);
        if ($ret === false) {
            socket_close($socket);
            return false;
        } else if ($ret == 0) {
            socket_close($socket);
            return false;
        } else {
            if (!socket_getpeername($socket, $host)) {
                socket_close($socket);
                return false;
            }
        }

        return $socket;
    }

}
?>