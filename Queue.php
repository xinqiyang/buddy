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
 * Queue Useage:
  $httpsqs = Queue::instance('queue');
  //http connect without Keep-Alive  (Use in Webserver)
  $result = $httpsqs->put($name, $data); //1. PUT text message into a queue. If PUT successful, return boolean: true. If an error occurs, return boolean: false. If queue full, return text: HTTPSQS_PUT_END
  $result = $httpsqs->get($name); //2. GET text message from a queue. Return the queue contents. If an error occurs, return boolean: false. If there is no unread queue message, return text: HTTPSQS_GET_END
  $result = $httpsqs->gets($name); //3. GET text message and pos from a queue. Return example: array("pos" => 7, "data" => "text message"). If an error occurs, return boolean: false. If there is no unread queue message, return: array("pos" => 0, "data" => "HTTPSQS_GET_END")
  $result = $httpsqs->status($name); //4. View queue status
  $result = $httpsqs->status_json($name); //5. View queue status in json. Return example: {"name":"queue_name","maxqueue":5000000,"putpos":130,"putlap":1,"getpos":120,"getlap":1,"unread":10}
  $result = $httpsqs->view($name, $pos); //6. View the contents of the specified queue pos (id). Return the contents of the specified queue pos.
  $result = $httpsqs->reset($host, $port, $charset, $name); //7. Reset the queue. If reset successful, return boolean: true. If an error occurs, return boolean: false
  $result = $httpsqs->maxqueue($name, $num); //8. Change the maximum queue length of per-queue. If change the maximum queue length successful, return boolean: true. If  it be cancelled, return boolean: false
  $result = $httpsqs->synctime($name, $num); //9. Change the interval to sync updated contents to the disk. If change the interval successful, return boolean: true. If  it be cancelled, return boolean: false
 */
$GLOBAL_HTTPSQS_PSOCKET = false;

/**
 * Queue class
 * @author xinqiyang
 *
 */
class Queue {

    private $host = '';
    private $port = '';
    private $mode = FALSE; //not use pconnect

    public function __construct($node = 'queue') {
        $config = C('queue.' . $node);
        if (!empty($config)) {
            $this->host = $config['host'];
            $this->port = $config['port'];
            $this->mode = false; //not use pconnect
        }
    }

    private function http_get($host, $port, $query) {
        $httpsqs_socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$httpsqs_socket) {
            return false;
        }
        $out = "GET ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        fwrite($httpsqs_socket, $out);
        $line = trim(fgets($httpsqs_socket));
        $header = $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($httpsqs_socket))) != "") {
            $header .= $line;
            if (strstr($line, "Content-Length:")) {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:")) {
                list($pos_key, $pos_value) = explode(" ", $line);
            }
        }
        if ($len < 0) {
            return false;
        }
        $body = @fread($httpsqs_socket, $len);
        fclose($httpsqs_socket);
        if (isset($pos_value)) {
            $result_array["pos"] = (int) $pos_value;
        }
        $result_array["data"] = $body;
        return $result_array;
    }

    private function http_post($host, $port, $query, $body) {
        $httpsqs_socket = @fsockopen($host, $port, $errno, $errstr, 5);
        if (!$httpsqs_socket) {
            return false;
        }
        $out = "POST ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Content-Length: " . strlen($body) . "\r\n";
        $out .= "Connection: close\r\n";
        $out .= "\r\n";
        $out .= $body;
        fwrite($httpsqs_socket, $out);
        $line = trim(fgets($httpsqs_socket));
        $header = $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($httpsqs_socket))) != "") {
            $header .= $line;
            if (strstr($line, "Content-Length:")) {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:")) {
                list($pos_key, $pos_value) = explode(" ", $line);
            }
        }
        if ($len < 0) {
            return false;
        }
        $body = @fread($httpsqs_socket, $len);
        fclose($httpsqs_socket);
        $result_array["pos"] = (int) $pos_value;
        $result_array["data"] = $body;
        return $result_array;
    }

    private function http_pget($host, $port, $query) {
        global $GLOBAL_HTTPSQS_PSOCKET;
        $hostport = md5($host . ":" . $port);
        if (!$GLOBAL_HTTPSQS_PSOCKET[$hostport]) {
            $GLOBAL_HTTPSQS_PSOCKET[$hostport] = @pfsockopen($host, $port, $errno, $errstr, 5);
        }
        if (!$GLOBAL_HTTPSQS_PSOCKET[$hostport]) {
            return false;
        }
        $out = "GET ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Connection: Keep-Alive\r\n";
        $out .= "\r\n";
        fwrite($GLOBAL_HTTPSQS_PSOCKET[$hostport], $out);
        $line = trim(fgets($GLOBAL_HTTPSQS_PSOCKET[$hostport]));
        if (empty($line) == true) {
            unset($GLOBAL_HTTPSQS_PSOCKET[$hostport]);
            $GLOBAL_HTTPSQS_PSOCKET[$hostport] = @pfsockopen($host, $port, $errno, $errstr, 5);
            if ($GLOBAL_HTTPSQS_PSOCKET[$hostport]) {
                fwrite($GLOBAL_HTTPSQS_PSOCKET[$hostport], $out);
                $line = trim(fgets($GLOBAL_HTTPSQS_PSOCKET[$hostport]));
            } else {
                return false;
            }
        }
        $header = $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($GLOBAL_HTTPSQS_PSOCKET[$hostport]))) != "") {
            $header .= $line;
            if (strstr($line, "Content-Length:")) {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:")) {
                list($pos_key, $pos_value) = explode(" ", $line);
            }
        }
        if ($len < 0) {
            return false;
        }
        $body = @fread($GLOBAL_HTTPSQS_PSOCKET[$hostport], $len);
        if (isset($pos_value)) {
            $result_array["pos"] = (int) $pos_value;
        }
        $result_array["data"] = $body;
        return $result_array;
    }

    private function http_ppost($host, $port, $query, $body) {
        global $GLOBAL_HTTPSQS_PSOCKET;
        $hostport = md5($host . ":" . $port);
        if (!$GLOBAL_HTTPSQS_PSOCKET[$hostport]) {
            $GLOBAL_HTTPSQS_PSOCKET[$hostport] = @pfsockopen($host, $port, $errno, $errstr, 5);
        }
        if (!$GLOBAL_HTTPSQS_PSOCKET[$hostport]) {
            return false;
        }
        $out = "POST ${query} HTTP/1.1\r\n";
        $out .= "Host: ${host}\r\n";
        $out .= "Content-Length: " . strlen($body) . "\r\n";
        $out .= "Connection: Keep-Alive\r\n";
        $out .= "\r\n";
        $out .= $body;
        fwrite($GLOBAL_HTTPSQS_PSOCKET[$hostport], $out);
        $line = trim(fgets($GLOBAL_HTTPSQS_PSOCKET[$hostport]));
        if (empty($line) == true) {
            unset($GLOBAL_HTTPSQS_PSOCKET[$hostport]);
            $GLOBAL_HTTPSQS_PSOCKET[$hostport] = @pfsockopen($host, $port, $errno, $errstr, 5);
            if ($GLOBAL_HTTPSQS_PSOCKET[$hostport]) {
                fwrite($GLOBAL_HTTPSQS_PSOCKET[$hostport], $out);
                $line = trim(fgets($GLOBAL_HTTPSQS_PSOCKET[$hostport]));
            } else {
                return false;
            }
        }
        $header = $line;
        list($proto, $rcode, $result) = explode(" ", $line);
        $len = -1;
        while (($line = trim(fgets($GLOBAL_HTTPSQS_PSOCKET[$hostport]))) != "") {
            $header .= $line;
            if (strstr($line, "Content-Length:")) {
                list($cl, $len) = explode(" ", $line);
            }
            if (strstr($line, "Pos:")) {
                list($pos_key, $pos_value) = explode(" ", $line);
            }
        }
        if ($len < 0) {
            return false;
        }
        $body = @fread($GLOBAL_HTTPSQS_PSOCKET[$hostport], $len);
        $result_array["pos"] = (int) $pos_value;
        $result_array["data"] = $body;
        return $result_array;
    }

    /**
     * put data to queue
     * @param string $name queue name
     * @param string $data queue data
     * @param string $charset charset
     */
    public function put($name, $data, $charset = 'utf-8') {
        $method = $this->mode ? 'http_ppost' : 'http_post';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=put", $data);
        if ($result["data"] == "HTTPSQS_PUT_OK") {
            return true;
        } else if ($result["data"] == "HTTPSQS_PUT_END") {
            return $result["data"];
        }
        return false;
    }

    /**
     * get data from queue
     * @param string $name queuename
     * @param string $charset charset
     */
    public function get($name, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=get");
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result["data"];
    }

    /**
     * GET text message and pos from a queue. 
     * Return example: array("pos" => 7, "data" => "text message"). 
     * If an error occurs, return boolean: false. 
     * If there is no unread queue message, return: array("pos" => 0, "data" => "HTTPSQS_GET_END")
     * @param string $name queuename
     * @param string $charset charset
     */
    public function gets($name, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=get");
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result;
    }

    /**
     * status of the queue
     * @param string $name  queuename
     * @param string $charset  chaset
     */
    public function status($name, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=status");
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result["data"];
    }

    /**
     * view data from queue
     * View the contents of the specified queue pos (id). 
     * Return the contents of the specified queue pos.
     * @param string $name  queue name
     * @param string $pos   position of the queue
     * @param string $charset charset
     */
    public function view($name, $pos, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=view&pos=" . $pos);
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result["data"];
    }

    /**
     * reset the queue
     * @param string $name queue name
     * @param string $charset charset
     */
    public function reset($name, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=reset");
        if ($result["data"] == "HTTPSQS_RESET_OK") {
            return true;
        }
        return false;
    }

    /**
     * set max length of the queue
     * @param string $name queuename
     * @param int $num  max length of queue
     * @param string $charset charset
     */
    public function maxqueue($name, $num, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=maxqueue&num=" . $num);
        if ($result["data"] == "HTTPSQS_MAXQUEUE_OK") {
            return true;
        }
        return false;
    }

    /**
     * return status use json format
     * @param string $name queue name
     * @param string $charset charset
     */
    public function status_json($name, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=status_json");
        if ($result == false || $result["data"] == "HTTPSQS_ERROR" || $result["data"] == false) {
            return false;
        }
        return $result["data"];
    }

    /**
     * synctime
     * Change the interval to sync updated contents to the disk.
     * If change the interval successful, return boolean: true. 
     * If  it be cancelled, return boolean: false
     * @param string $name queue name
     * @param int $num
     * @param string $charset charset
     */
    public function synctime($name, $num, $charset = 'utf-8') {
        $method = $this->mode ? 'http_pget' : 'http_get';
        $result = $this->$method($this->host, $this->port, "/?charset=" . $charset . "&name=" . $name . "&opt=synctime&num=" . $num);
        if ($result["data"] == "HTTPSQS_SYNCTIME_OK") {
            return true;
        }
        return false;
    }

}