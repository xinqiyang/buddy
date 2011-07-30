<?php
/**
 * Stat Logic Class
 */
class StatLogic {

    /**
     * get max id from table
     * @param string $table
     * @param string $where
     * @param string $field
     * @return mixed array/false
     */
    public static function getmaxid($table,$where,$field='id')
    {
        $sql = sprintf("SELECT max(%s) as max FROM tb_%s WHERE %s LIMIT 1",$field,$table,$where);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if ($r === false) {
            UB_LOG_FATAL('SELECT ERROR %s', $sql);
            return $r;
        }
        return $r[0]['max'];
    }

    /**
     * get min id from table
     * @param string $table
     * @param string $where
     * @param string $field
     * @return mixed array/false
     */
    public static function getminid($table,$where,$field='id')
    {
        $sql = sprintf("SELECT min(%s) as min FROM tb_%s WHERE %s LIMIT 1",$field,$table,$where);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if ($r === false) {
            UB_LOG_FATAL('SELECT ERROR %s', $sql);
            return $r;
        }
        return $r[0]['min'];
    }

    /**
     * get count
     * @param string $table
     * @param string $where
     * @return mixed array/false
     */
    public static function getcount($table,$where,$field='id')
    {
        $sql = sprintf("SELECT count(%s) as count FROM tb_%s WHERE %s LIMIT 1",$field,$table,$where);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if ($r === false) {
            UB_LOG_FATAL('SELECT ERROR %s', $sql);
            return $r;
        }
        if(isset ($r[0]['count']))
        {
            return $r[0]['count'];
        }
        return 0;
    }

    /**
     * exist
     * @param string $table
     * @param string $where
     * @return mixed array/id
     */
    public static function exist($table,$where)
    {
        $sql = sprintf("SELECT id FROM tb_%s WHERE %s LIMIT 1",$table,$where);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if ($r === false) {
            UB_LOG_FATAL('SELECT ERROR %s', $sql);
            return $r;
        }
        return $r[0]['id'];
    }

    /**
     * select data by opcode from tb_metadata
     * @param <type> $opcode
     * @return <type>
     */
    public static function smetadatabyopcode($opcode, $start=0, $end=0) {
        $end = $end > 0 ? $end : strtotime('today');
        $start = $start > 0 ? $start : $end-84400;
        $sql = sprintf("SELECT id,created_time,data0,data1,data2,data3,data4,data5,data6,data7,data8,data9 FROM tb_metadata WHERE opcode=%s AND created_time>=%s AND created_time<%s", $opcode, $start, $end);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if ($r === false) {
            UB_LOG_FATAL('SELECT ERROR %s', $sql);
            return $r;
        }
        return $r;
    }

    /**
     * select user metadata from metadata by opcode and user_id
     * @param int $opcode
     * @param mixed $user_id
     * @param int $start
     * @param int $end
     * @return mixed false/array
     */
    public static function sumetadatabyopcode($opcode, $user_id=0, $start=0, $end=0) {
        //select by user_id and opcode
        if (is_int($user_id) && $user_id > 0) {
            $table = $user_id % 100;
            $sql = sprintf("SELECT id,created_time,data0,data1,data2,data3,data4,data5,data6,data7,data8,data9 FROM tb_metadata_%s WHERE user_id=%s AND opcode=%s AND created_time>=%s AND created_time<%s", $table, $user_id, $opcode, $start, $end);
        } elseif (is_array($user_id) && count($user_id)) {
            //use in select
            $sql = sprintf("SELECT id,created_time,data0,data1,data2,data3,data4,data5,data6,data7,data8,data9 FROM tb_metadata WHERE user_id=%s AND opcode=%s AND created_time>=%s AND created_time<%s", $user_id, $opcode, $start, $end);
        } else {
            //select all tables
        }
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if ($r === false) {
            UB_LOG_FATAL('SELECT ERROR %s', $sql);
            return $r;
        }
        return $r;
    }

     /**
     * get the update data
     * @param array $arr
     * @return string
     */
    public static function setupdatefield($arr) {
        $str = '';
        if (is_array($arr) && count($arr)) {
            foreach ($arr as $key => $value) {
                if ($key !== 'id') {
                    $str .= "'$key'='$value',";
                }
            }
            if (strlen($str) > 2) {
                return rtrim($str, ',');
            }
        }
        return '';
    }

    /**
     *
     * @param int $field
     * @param int $user_id
     * @param int $start timestamp
     * @param int $end timestamp
     * @param int $rtype default return count,if set 0 return array userids
     * @param int $defaultday -1 default
     * @return mixed array/int
     */
    public function smetadatadistinctuser($field, $user_id, $start=0, $end=0, $rtype=1, $defaultday = -1) {
        $r = 0;
        $table = $user_id % 100;
        if ($user_id > 0) {
            //set default time
            $start = $start > 0 ? $start : time() + 24 * 3600 * $defaultday;
            $end = $end > 0 ? $end : time();
            $fields = $rtype === 1 ? 'count(distinct user_id)' : 'distinct user_id';
            $sql = sprintf("SELECT %s from tb_metadata_%s where created_time>=%s and created_time<%s", $fields, $table, $start, $end);
            $db = getdb('sns_stat','dbStat');
            $r = $db->query($sql);
            if ($r === false) {
                //LOG the error

                return false;
            }
            return $r;
        }
        return $r;
    }

    /**
     * select metadata by id
     * @param int $id
     * @param int $user_id
     * @return mixed
     */
    public static function sumetadatabyid($id,$user_id) {
        $r = false;
        if (is_int($user_id) && $user_id > 0) {
            $table = $user_id % 100;
            $sql = sprintf("SELECT id,created_time,data0,data1,data2,data3,data4,data5,data6,data7,data8,data9 FROM tb_metadata_%s WHERE id=%s ", $table, $id);
            $db = getdb('sns_stat','dbStat');
            $r = $db->query($sql);
            if ($r === false) {
                UB_LOG_FATAL('SELECT ERROR %s', $sql);
                return $r;
            }
            return $r;
        }
        return $r;
    }

    /**
     * create a row to tb_result
     * @param array $instance array('id'=>'','timeline'=>'','opcode'=>'','data'=>'','created_time'=>'');
     * @return mixed false/1
     */
    public static function cresult($instance)
    {
        extract($instance);
        $sql = sprintf("INSERT INTO tb_timeline (id,timeline,opcode,data,created_time) VALUES (%s,%s,%s,%s)",$id,$timeline,$opcode,$data,$created_time);
        $db = getdb('sns_stat','dbStat');
        $r = $db->execute($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select data from tb_result by opcode and timeline
     * @param int $opcode
     * @param int $timeline
     * @return mixed array/bool
     */
    public static function sresultbyopcode($opcode,$timeline=0)
    {
        $timeline = $timeline > 0 ? $timeline : strtotime('today') - 84400;
        $sql = sprintf("SELECT id,timeline,data,created_time FROM tb_result WHERE opcode=%s AND timeline=%s",$opcode,$timeline);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if($r === false)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * create a row to tb_user_info
     * @param array $instance array('id'=>'','user_id'=>'','protype_id'=>'','value'=>'','created_time'=>'')
     * @return mixed false/1
     */
    public static function cuser_info($instance)
    {
        extract($instance);
        $sql = sprintf("INSERT INTO tb_user_info (id,user_id,protype_id,value,created_time) VALUES (%s,%s,%s,%s,%s)",$id,$user_id,$protype_id,$value,$created_time);
        $db = getdb('sns_stat','dbStat');
        $r = $db->execute($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * Update tb_user_info by user_id and protype_id
     * @param int $user_id
     * @param int $protype_id
     * @param string $value string
     * @return mixed false/1
     */
    public static function uuser_infobyupid($user_id,$protype_id,$value)
    {
        $sql = sprintf("UPDATE tb_user_info SET value=%s WHERE user_id=%s AND protype_id=%s",$value,$user_id,$protype_id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->execute($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * Update tb_user_info by id
     * @param bigint $id
     * @param string $value
     * @return mixed false/1
     */
    public static function uuser_infobyid($id,$value)
    {
        $sql = sprintf("UPDATE tb_user_info SET value=%s WHERE id=%s",$value,$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->execute($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select value from tb_user_info by user_id and protype_id
     * @param bigint $user_id
     * @param int $protype_id
     * @return mixed false/1
     */
    public static function suser_infobyupiid($user_id,$protype_id)
    {
        $sql = sprintf("SELECT id,value FROM tb_user_info WHERE user_id=%s AND protype_id=%s",$user_id,$protype_id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select data from tb_user by user_id
     * @param bigint $user_id
     * @return mixed array/false
     */
    public static function suser_infobyuserid($user_id)
    {
        $sql = sprintf("SELECT id,protype_id,value FROM tb_user_info WHERE user_id=%s",$user_id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }


    /**
     * select protype_id from tb_user_info by protype_id
     * @param int $protype_id
     * @return mixed array/false
     */
    public static function suser_infobyprotype_id($protype_id)
    {
        $sql = sprintf("SELECT id,user_id,value FROM tb_user_info WHERE protype_id=%s",$protype_id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select tb_user_info by id
     * @param bigint $id
     * @return mixed false/array
     */
    public static function suser_infobyid($id)
    {
        $sql = sprintf("SELECT id,user_id,protype_id,value FROM tb_user_info WHERE pid=%s",$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    public static function ctimeline($instance)
    {
        extract($instance);
        $sql = sprintf("INSERT INTO tb_timeline (id,timeline,data,created_time) VALUES ()",$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->execute($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * update tb_timeline by id
     * @param bigint $id
     * @param int $opcode
     * @param int $protype_id
     * @param string $data
     * @param int $timetype
     * @return mixed array/false
     */
    public static function utimelinebyid($id,$opcode,$protype_id,$data,$timetype)
    {
        $sql = sprintf("UPDATE tb_timeline set data=%s,timetype=%s WHERE id=%s",$data,$timetype,$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->execute($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }


    /**
     * select timeline by opcode
     * @param int $opcode
     * @param int $start
     * @param int $end
     * @return mixed array/false
     */
    public static function stimelinebyopcode($opcode,$start,$end)
    {
        $end = $end > 0 ? $end : strtotime('today');
        $start = $start > 0 ? $start : $end - 84400;
        $sql = sprintf("SELECT id,timeline,protype_id,data,timetype,created_time FROM tb_timeline WHERE opcode=%s AND timeline>=%s AND timeline<=%s",$opcode,$start,$end);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select timeline by id
     * @param int $id
     * @return mixed array/false
     */
    public static function stimelinebyid($id)
    {
        $sql = sprintf("SELECT timeline,protype_id,data,timetype,created_time FROM tb_timeline WHERE id=%s",$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select category by id
     * @param int $id
     * @return mixed array/false
     */
    public static function scategorybyid($id)
    {
        $sql = sprintf("SELECT title,pid,created_time FROM tb_category WHERE id=%s and status=1",$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select protype by id
     * @param int $id
     * @return mixed array/false
     */
    public static function sprotypebyid($id)
    {
        $sql = sprintf("SELECT key,value,category_id,created_time FROM tb_protype WHERE id=%s and status=1",$id);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }

    /**
     * select from opcode by opcode
     * @param int $opcode
     * @return mixed array/false
     */
    public static function sopcodebyopcode($opcode)
    {
        $sql = sprintf("SELECT title,data,note,created_time FROM tb_opcode WHERE opcode=%s and status=1",$opcode);
        $db = getdb('sns_stat','dbStat');
        $r = $db->query($sql);
        if(!$r)
        {
            UB_LOG_FATAL('ERROR %s,SQL:%s',$db->getError(),$sql);
            return false;
        }
        return $r;
    }



}