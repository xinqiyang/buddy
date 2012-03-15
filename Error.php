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
 * Buddy Error Code class
 * if u use other code then modify this please
 * @author xinqiyang
 *
 */
class Error {

    const SUCCESS = 0;            //操作成功
    const BUSY = 1;  //系统繁忙



    /**
     * 状态码	含义
     *  
     */
    //OK	请求成功
    const CREATED = 201; //CREATED	创建成功
    const ACCPTED = 202; //ACCEPTED	更新成功
    const BAD_REQUEST = 400; //BAD REQUEST	请求的地址不存在或者包含不支持的参数
    const UNAUTHORIZED = 401; //UNAUTHORIZED	未授权
    const FORBIDDEN = 403; //FORBIDDEN	被禁止访问
    const NOT_FOUND = 404; //NOT FOUND	请求的资源不存在
    const REDIS_SET_ERROR = 405; //redis set error
    const REDIS_GET_ERROR = 406; //redis get error
    const REDIS_COUNTSET_ERROR = 407;
    const REDIS_OBJECTSET_ERROR = 408;
    const UNKWON_ERROR = 500; //未知错误 INTERNAL SERVER ERROR	内部错误
    const DB_ERROR = 501; //数据库操作错误
    const PARAM_ERROR = 502; //接口参数错误
    const NOT_FINISHED = 503; //功能未实现
    const ACCOUNT_NAME_DUP = 100001; //用户重名
    const ACCOUNT_EMAIL_DUP = 10002; //邮箱重复
    const ACCOUNT_MOBILE_DUP = 10003; //手机号重复
    const ACCOUNT_PWD_CHG_OLDERROR = 10004; //旧密码错误
    const ACCOUNT_LOGIN_PASSERROR = 10005; //登录密码错误
    const ACCOUNT_LOGIN_FORBIDEN = 10006; //被禁止登录
    const INCLUDE_DANGER_WORDS = 10007; //包含危险关键字
    const BRAND_NAME_DUMP = 10008; //品牌名称重复
    const BRAND_ENNAME_DUMP = 10009; //品牌英文名称重复
    const USERLOCATION_GET_GEOSERROR = 10010; //获取sina地理位置信息失败
    const BRAND_NAME_DUP = 10020; //品牌的名称重复
    const BRAND_ENNAME_DUP = 10021; //品牌的英文名称重复
    const TAG_NOT_FIND = 10030; //tag not find
    const STREAM_SEND_DUMP = 10040; //发送的消息重复
    const URL_NOT_AVALIDE = 10050; //
    //验证相关
    const LOGIN_INFO_NOTFULL = 130; //登陆信息不完整
    const LOGIN_IMAGECODE_ERR = 131; //验证码错误
    const LOGIN_UN_PWD_ERR = 132; //用户名密码错误
    const LOGIN_USER_NOTFOUND = 133; //用户不存在
    //找回密码
    const USER_EMAIL_SEND_ERR = 151; //email发送失败
    const GROUP_COUNT_LIMIT = 1001; //群只能建4个
    const NotBeFollow = 2001; //不是follow你的人不能发送
    const UTIL_COLUMN_MALFORM_ERROR = 10001; //字段格式错误
    const UTIL_SENDMAIL_TPL_ERROR = 10011; //发邮件模板错误
    const UTIL_SENDMAIL_UNKNOWN_ERROR = 10012; //发邮件未知错误
    const WELCOME_NEWBE = 3001; //欢迎新来的同学
    const NOT_ACTION_MYSELF = 3838; //不能对自己操作

    /**
     * 消息函数
     * @var array
     */

    private static $_arrErrMap = array(
        self::USER_NAME_DUP => '输入的用户名已存在',
        self::USER_NAME_MALFORM => '用户名不允许包含特殊字符',
        self::USER_NAME_TOOSHORT => '用户名太短',
        self::USER_NAME_EMPTY => '用户名为空',
        self::USER_INFO_TOOLONG => '信息字段太长：',
        self::UNKWON_ERROR => '未知错误',
        self::DB_ERROR => '数据库操作错误',
        self::PARAM_ERROR => '函数参数错误',
        self::NOT_FINISHED => '功能未实现',
        self::NotBeFollow => '不在关注列表中',
        self::UTIL_COLUMN_MALFORM_ERROR => '字段格式错误',
        self::UTIL_SENDMAIL_TPL_ERROR => '发邮件模板错误',
        self::UTIL_SENDMAIL_UNKNOWN_ERROR => '发邮件未知错误',
    );

    /**
     * get error msg
     * @param string $intMsgId
     */
    public static function getMsg($intMsgId = 0) {
        if (isset($intMsgId) && isset(self::$_arrErrMap[$intMsgId])) {
            return self::$_arrErrMap[$intMsgId];
        }
        logNotice("ERROR CODE NOT INFO ID:%s", $intMsgId);
        return '';
    }

}

