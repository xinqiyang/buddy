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
 * Time functions
 * 
 * @author xinqiyang
 *
 */
class Time {
	
	/**
	 * show friend time
	 * @param $datetime time
	 * @return string friend time show
	 */
	public static function timeAgo($datetime, $nowtime = 0)
	{
		$datetime = $datetime>0 ? $datetime : strtotime($datetime);
		if (empty($nowtime))
		{
			$nowtime = time();
		}
		$timediff = $nowtime - $datetime;
		$timediff = $timediff >= 0 ? $timediff : $datetime - $nowtime;
		// 秒
		if ($timediff < 60)
		{
			return $timediff . '秒前';
		}
		// 分
		if ($timediff < 3600 && $timediff >= 60)
		{
			return intval($timediff / 60) . '分钟前';
		}
		// 今天
		$today = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
		if ($datetime >= $today)
		{
			return date('今天 H:i', $datetime);
		}
		// 昨天
		$yestoday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
		if ($datetime >= $yestoday)
		{
			return date('昨天 H:i', $datetime);
		}
		// 今年月份
		$this_year = mktime(0, 0, 0, 1, 1, date('Y'));
		if ($datetime >= $this_year)
		{
			return date('m月d日 H:i', $datetime);
		}
		// 往年
		return date('Y年m月d日', $datetime);
	}
	
	
	public static function tranTime($time) {  
	    $rtime = date("m-d H:i",$time);  
	    $htime = date("H:i",$time);  
	      
	    $time = time() - $time;  
	  
	    if ($time < 60) {  
	        $str = '刚刚';  
	    }  
	    elseif ($time < 60 * 60) {  
	        $min = floor($time/60);  
	        $str = $min.'分钟前';  
	    }  
	    elseif ($time < 60 * 60 * 24) {  
	        $h = floor($time/(60*60));  
	        $str = $h.'小时前 '.$htime;  
	    }  
	    elseif ($time < 60 * 60 * 24 * 3) {  
	        $d = floor($time/(60*60*24));  
	        if($d==1)  
	           $str = '昨天 '.$rtime;  
	        else  
	           $str = '前天 '.$rtime;  
	    }  
	    else {  
	        $str = $rtime;  
	    }  
	    return $str;  
	}
	

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

    
    public static function lastMonth() {
        return mktime(0, 0, 0, date("m")-1, date("d"), date("Y"));
    }

    //@TODO NEED TO BE CHANGE
    public static function thisQuarter($time)
    {
        $day = mktime(0,0,0,date('n')-(date('n')-1)%3,1,date('Y'));
        
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
        if (!empty($begin) && !empty($end)) {
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
                return $arr;
            }
        }
        return false;
    }
    
    /**
     * nongli status 
     * time is string  2011-03-21 11:33:22   or timestap
     * @param mixed $varTheDay string/int date or timestamp
     */
    public static function nongLi($time)
	{
		$time = is_int($time) ? date('Y-m-d',$time) : date('Y-m-d',strtotime($time));
		$arrTime = explode('-', $time);
		if(!isset($arrTime[0]) || !isset($arrTime[1]) || !isset($arrTime[2])){
			return false;
		}
		$varTheDay['year'] = $arrTime[0];
		$varTheDay['mon'] = $arrTime[1];
		$varTheDay['day'] = $arrTime[2];
		$everymonth=array(
		                0=>array(8,0,0,0,0,0,0,0,0,0,0,0,29,30,7,1),
		                1=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,8,2),
		                2=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,9,3),
		                3=>array(5,29,30,29,30,29,29,30,29,29,30,30,29,30,10,4),
		                4=>array(0,30,30,29,30,29,29,30,29,29,30,30,29,0,1,5),
		                5=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,2,6),
		                6=>array(4,29,30,30,29,30,29,30,29,30,29,30,29,30,3,7),
		                7=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,4,8),
		                8=>array(0,30,29,29,30,30,29,30,29,30,30,29,30,0,5,9),
		                9=>array(2,29,30,29,29,30,29,30,29,30,30,30,29,30,6,10),
		                10=>array(0,29,30,29,29,30,29,30,29,30,30,30,29,0,7,11),
		                11=>array(6,30,29,30,29,29,30,29,29,30,30,29,30,30,8,12),
		                12=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,9,1),
		                13=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,10,2),
		                14=>array(5,30,30,29,30,29,30,29,30,29,30,29,29,30,1,3),
		                15=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,2,4),
		                16=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,3,5),
		                17=>array(2,30,29,29,30,29,30,30,29,30,30,29,30,29,4,6),
		                18=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,5,7),
		                19=>array(7,29,30,29,29,30,29,29,30,30,29,30,30,30,6,8),
		                20=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,7,9),
		                21=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,8,10),
		                22=>array(5,30,29,30,30,29,29,30,29,29,30,29,30,30,9,11),
		                23=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,10,12),
		                24=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,1,1),
		                25=>array(4,30,29,30,29,30,30,29,30,30,29,30,29,30,2,2),
		                26=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,3,3),
		                27=>array(0,30,29,29,30,29,30,29,30,29,30,30,30,0,4,4),
		                28=>array(2,29,30,29,29,30,29,29,30,29,30,30,30,30,5,5),
		                29=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,6,6),
		                30=>array(6,29,30,30,29,29,30,29,29,30,29,30,30,29,7,7),
		                31=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,8,8),
		                32=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,9,9),
		                33=>array(5,29,30,30,29,30,30,29,30,29,30,29,29,30,10,10),
		                34=>array(0,29,30,29,30,30,29,30,29,30,30,29,30,0,1,11),
		                35=>array(0,29,29,30,29,30,29,30,30,29,30,30,29,0,2,12),
		                36=>array(3,30,29,29,30,29,29,30,30,29,30,30,30,29,3,1),
		                37=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,4,2),
		                38=>array(7,30,30,29,29,30,29,29,30,29,30,30,29,30,5,3),
		                39=>array(0,30,30,29,29,30,29,29,30,29,30,29,30,0,6,4),
		                40=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,7,5),
		                41=>array(6,30,30,29,30,30,29,30,29,29,30,29,30,29,8,6),
		                42=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,9,7),
		                43=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,10,8),
		                44=>array(4,30,29,30,29,30,29,30,29,30,30,29,30,30,1,9),
		                45=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,2,10),
		                46=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,3,11),
		                47=>array(2,30,30,29,29,30,29,29,30,29,30,29,30,30,4,12),
		                48=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,5,1),
		                49=>array(7,30,29,30,30,29,30,29,29,30,29,30,29,30,6,2),
		                50=>array(0,29,30,30,29,30,30,29,29,30,29,30,29,0,7,3),
		                51=>array(0,30,29,30,30,29,30,29,30,29,30,29,30,0,8,4),
		                52=>array(5,29,30,29,30,29,30,29,30,30,29,30,29,30,9,5),
		                53=>array(0,29,30,29,29,30,30,29,30,30,29,30,29,0,10,6),
		                54=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,1,7),
		                55=>array(3,29,30,29,30,29,29,30,29,30,29,30,30,30,2,8),
		                56=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,3,9),
		                57=>array(8,30,29,30,29,30,29,29,30,29,30,29,30,29,4,10),
		                58=>array(0,30,30,30,29,30,29,29,30,29,30,29,30,0,5,11),
		                59=>array(0,29,30,30,29,30,29,30,29,30,29,30,29,0,6,12),
		                60=>array(6,30,29,30,29,30,30,29,30,29,30,29,30,29,7,1),
		                61=>array(0,30,29,30,29,30,29,30,30,29,30,29,30,0,8,2),
		                62=>array(0,29,30,29,29,30,29,30,30,29,30,30,29,0,9,3),
		                63=>array(4,30,29,30,29,29,30,29,30,29,30,30,30,29,10,4),
		                64=>array(0,30,29,30,29,29,30,29,30,29,30,30,30,0,1,5),
		                65=>array(0,29,30,29,30,29,29,30,29,29,30,30,29,0,2,6),
		                66=>array(3,30,30,30,29,30,29,29,30,29,29,30,30,29,3,7),
		                67=>array(0,30,30,29,30,30,29,29,30,29,30,29,30,0,4,8),
		                68=>array(7,29,30,29,30,30,29,30,29,30,29,30,29,30,5,9),
		                69=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,6,10),
		                70=>array(0,30,29,29,30,29,30,30,29,30,30,29,30,0,7,11),
		                71=>array(5,29,30,29,29,30,29,30,29,30,30,30,29,30,8,12),
		                72=>array(0,29,30,29,29,30,29,30,29,30,30,29,30,0,9,1),
		                73=>array(0,30,29,30,29,29,30,29,29,30,30,29,30,0,10,2),
		                74=>array(4,30,30,29,30,29,29,30,29,29,30,30,29,30,1,3),
		                75=>array(0,30,30,29,30,29,29,30,29,29,30,29,30,0,2,4),
		                76=>array(8,30,30,29,30,29,30,29,30,29,29,30,29,30,3,5),
		                77=>array(0,30,29,30,30,29,30,29,30,29,30,29,29,0,4,6),
		                78=>array(0,30,29,30,30,29,30,30,29,30,29,30,29,0,5,7),
		                79=>array(6,30,29,29,30,29,30,30,29,30,30,29,30,29,6,8),
		                80=>array(0,30,29,29,30,29,30,29,30,30,29,30,30,0,7,9),
		                81=>array(0,29,30,29,29,30,29,29,30,30,29,30,30,0,8,10),
		                82=>array(4,30,29,30,29,29,30,29,29,30,29,30,30,30,9,11),
		                83=>array(0,30,29,30,29,29,30,29,29,30,29,30,30,0,10,12),
		                84=>array(10,30,29,30,30,29,29,30,29,29,30,29,30,30,1,1),
		                85=>array(0,29,30,30,29,30,29,30,29,29,30,29,30,0,2,2),
		                86=>array(0,29,30,30,29,30,30,29,30,29,30,29,29,0,3,3),
		                87=>array(6,30,29,30,29,30,30,29,30,30,29,30,29,29,4,4),
		                88=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,5,5),
		                89=>array(0,30,29,29,30,29,29,30,30,29,30,30,30,0,6,6),
		                90=>array(5,29,30,29,29,30,29,29,30,29,30,30,30,30,7,7),
		                91=>array(0,29,30,29,29,30,29,29,30,29,30,30,30,0,8,8),
		                92=>array(0,29,30,30,29,29,30,29,29,30,29,30,30,0,9,9),
		                93=>array(3,29,30,30,29,30,29,30,29,29,30,29,30,29,10,10),
		                94=>array(0,30,30,30,29,30,29,30,29,29,30,29,30,0,1,11),
		                95=>array(8,29,30,30,29,30,29,30,30,29,29,30,29,30,2,12),
		                96=>array(0,29,30,29,30,30,29,30,29,30,30,29,29,0,3,1),
		                97=>array(0,30,29,30,29,30,29,30,30,29,30,30,29,0,4,2),
		                98=>array(5,30,29,29,30,29,29,30,30,29,30,30,29,30,5,3),
		                99=>array(0,30,29,29,30,29,29,30,29,30,30,30,29,0,6,4),
		                100=>array(0,30,30,29,29,30,29,29,30,29,30,30,29,0,7,5),
		                101=>array(4,30,30,29,30,29,30,29,29,30,29,30,29,30,8,6),
		                102=>array(0,30,30,29,30,29,30,29,29,30,29,30,29,0,9,7),
		                103=>array(0,30,30,29,30,30,29,30,29,29,30,29,30,0,10,8),
		                104=>array(2,29,30,29,30,30,29,30,29,30,29,30,29,30,1,9),
		                105=>array(0,29,30,29,30,29,30,30,29,30,29,30,29,0,2,10),
		                106=>array(7,30,29,30,29,30,29,30,29,30,30,29,30,30,3,11),
		                107=>array(0,29,29,30,29,29,30,29,30,30,30,29,30,0,4,12),
		                108=>array(0,30,29,29,30,29,29,30,29,30,30,29,30,0,5,1),
		                109=>array(5,30,30,29,29,30,29,29,30,29,30,29,30,30,6,2),
		                110=>array(0,30,29,30,29,30,29,29,30,29,30,29,30,0,7,3),
		                111=>array(0,30,29,30,30,29,30,29,29,30,29,30,29,0,8,4),
		                112=>array(4,30,29,30,30,29,30,29,30,29,30,29,30,29,9,5),
		                113=>array(0,30,29,30,29,30,30,29,30,29,30,29,30,0,10,6),
		                114=>array(9,29,30,29,30,29,30,29,30,30,29,30,29,30,1,7),
		                115=>array(0,29,30,29,29,30,29,30,30,30,29,30,29,0,2,8),
		                116=>array(0,30,29,30,29,29,30,29,30,30,29,30,30,0,3,9),
		                117=>array(6,29,30,29,30,29,29,30,29,30,29,30,30,30,4,10),
		                118=>array(0,29,30,29,30,29,29,30,29,30,29,30,30,0,5,11),
		                119=>array(0,30,29,30,29,30,29,29,30,29,29,30,30,0,6,12),
		                120=>array(4,29,30,30,30,29,30,29,29,30,29,30,29,30,7,1)
		               );
		$mten=array("null","甲","乙","丙","丁","戊","己","庚","辛","壬","癸");
		//$mtwelve=array("null","子(鼠)","丑(牛)","寅(虎)","卯(兔)","辰(龙)",
		//             "巳(蛇)","午(马)","未(羊)","申(猴)","酉(鸡)","戌(狗)","亥(猪)");
		$mtwelve=array("null","子","丑","寅","卯","辰",
		             "巳","午","未","申","酉","戌","亥");
		$mmonth=array("闰","正","二","三","四","五","六",
		            "七","八","九","十","十一","十二","月");
		$mday=array("null","初一","初二","初三","初四","初五","初六","初七","初八","初九","初十",
		          "十一","十二","十三","十四","十五","十六","十七","十八","十九","二十",
		          "廿一","廿二","廿三","廿四","廿五","廿六","廿七","廿八","廿九","三十");
		$total=11;
		$mtotal=0;
		if($varTheDay["year"]<1901 || $varTheDay["year"]>2020) 
		{
			return false;
		}
		
		$cur_wday=$varTheDay["day"];
		
		for($y=1901;$y<$varTheDay["year"];$y++) 
		{ 
		   $total+=365;
		   if ($y%4==0) $total++;
		}
		
		switch($varTheDay["mon"]) { 
		     case 12:
		          $total+=30;
		     case 11:
		          $total+=31;
		     case 10:
		          $total+=30;
		     case 9:
		          $total+=31;
		     case 8:
		          $total+=31;
		     case 7:
		          $total+=30;
		     case 6:
		          $total+=31;
		     case 5:
		          $total+=30;
		     case 4:
		          $total+=31;
		     case 3:
		          $total+=28;
		     case 2:
		          $total+=31;
		}
		
		if($varTheDay["year"]%4 == 0 && $varTheDay["mon"]>2) $total++;
		
		$total=$total+$varTheDay["day"]-1;
		
		$flag1=0;
		$j=0;
		while ($j<=120){
		  $i=1;
		  while ($i<=13){
		        $mtotal+=$everymonth[$j][$i];
		        if ($mtotal>=$total){
		             $flag1=1;
		             break;
		        }
		        $i++;
		  }
		  if ($flag1==1) break;
		  $j++;
		}
		
		if($everymonth[$j][0]<>0 and $everymonth[$j][0]<$i){
		  $mm=$i-1;
		}
		else{
		  $mm=$i;
		}
		
		if($i==$everymonth[$j][0]+1 and $everymonth[$j][0]<>0) {
		  $nlmon=$mmonth[0].$mmonth[$mm];
		}
		else {
		  $nlmon=$mmonth[$mm].$mmonth[13];
		}
		
		$md=$everymonth[$j][$i]-($mtotal-$total);
		if($md > $everymonth[$j][$i])
		  $md-=$everymonth[$j][$i];
		$nlday=$mday[$md];
		
		return $mten[$everymonth[$j] [14]].$mtwelve[$everymonth[$j][15]]."年".$nlmon.$nlday;
	}
}