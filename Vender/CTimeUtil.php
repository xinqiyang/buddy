<?php
/**
 * get time util
 * TODO: nedd add  hours statistics
 * @author yangxinqi (yangxinqi@baidu.com)
 * @copyright baidu.com
 */
class TimeUtil {

    /**
     * yesterday time
     * @return int timestamp
     */
    public static function yesterday() {
        return strtotime('-1 day');
    }

    /**
     * today first timestamp
     * @return int timestamp
     */
    public static function today() {
        return strtotime('today');
    }

    //TODO: NEED DO
    public static function lastMonth() {
        
    }

    //TODO: NEED DO
    public static function thisQuarter($format)
    {
        $begin = mktime(0,0,0,date('n')-(date('n')-1)%3,1,date('Y'));
    }

    /**
     * return this Month date array
     * @return array
     */
    public static function thisMonth($format = 'Ymd')
    {
        return self::fromtoTime(mktime(0,0,0,date('n'),1,date('Y')), mktime(0,0,0,date('n'),date('t'),date('Y')),$format);
    }

    /**
     * lastWeek date arr
     * @return array
     */
    public static function lastWeek($format='Ymd') {
        return self::natureWeek(strtotime("-1 week Sunday"), $format);
    }

    /**
     * this week date arr
     * @param string $format int/timeformat
     * @return array
     */
    public static function thisWeek($format='Ymd') {
        return self::natureWeek('', $format);
    }

    /**
     * get natureweek first and end
     * @param mixed $date null/string/int
     * @param string $format int/time format
     * @return array
     */
    public static function natureWeek($date='',$format='Ymd') {
        $timestamp = empty($date) ? strtotime('today') : (is_int($date) ? $date : strtotime($date));
        $sdate = $timestamp - (date('N', $timestamp) - 1) * 86400;
        $edate = $timestamp + (7 - date('N', $timestamp)) * 86400;
        return self::fromtoTime($sdate, $edate,$format);
    }


    /**
     * from begin to end time
     * @param mixed $begin  timestamp/string
     * @param mixed $end   timestamp/string
     * @param string $format int/time format, Ymd/Y-m-d/Y-m-d 00:00:00
     * @return mixed array/false
     */
    public static function fromtoTime($begin, $end, $format='Ymd') {
        if ($begin && $end) {
            $begin = is_int($begin) ? $begin : strtotime($begin);
            $end = is_int($end) ? $end : strtotime($end);
            $arr = array();
            if ($end > $begin) {
                $days = ceil(($end - $begin) / (24 * 3600));
                if ($days) {
                    for ($i = $days; $i >= 0; $i--) {
                        if($format === 'int')
                        {
                            array_push($arr, $begin + 24 * $i * 3600);
                        }else{
                            array_push($arr, date($format, $begin + 24 * $i * 3600));
                        }
                    }
                }
            }
            return $arr;
        }
        return false;
    }
}