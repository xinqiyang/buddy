<?php
/**
 * 商品分享公共文件
 * 
 * @author liguang 2011-11-27
 */
class ShareGoods
{
    private $goodsDomainClass = array(
    'item.taobao.com' => 'taobaoGoods', 
    'item.tmall.com' => 'taobaoGoods',
    'detail.tmall.com' => 'taobaoGoods'
    );
    private $moduleConfig;
    private $url;
    private $goodsModule;
    /**
     * 自动识别分享商品的来源
     */
    public function __construct ($url)
    {
    	$this->moduleConfig = C('goods');
        $intRs = preg_match("/^(http:\/\/|https:\/\/)/", $url, $match);
        if (intval($intRs) == 0) {
            $url = "http://" . $url;
        }
        $arrRs = parse_url($url);
        if (isset($arrRs['host']) &&
         isset($this->goodsDomainClass[$arrRs['host']])) {
            if (isset(
            $this->moduleConfig[$this->goodsDomainClass[$arrRs['host']]])) {
                $this->goodsModule = new $this->goodsDomainClass[$arrRs['host']](
                $this->moduleConfig[$this->goodsDomainClass[$arrRs['host']]]);
            }
        }
        $this->url = $url;
    }
    /**
     * 返回商品ID
     */
    public function getGoodsId ()
    {
        if ($this->goodsModule) {
            return $this->goodsModule->getId($this->url);
        } else {
            return false;
        }
    }
    /**
     * 返回商品信息
     */
    public function getGoods ()
    {
        if ($this->goodsModule) {
            return $this->goodsModule->getGoods($this->url);
        } else {
            return false;
        }
    }
    /**
     * 获取该商品的标识，用于检测是否已经采集
     */
    public function getKey ()
    {
        if ($this->goodsModule) {
            return $this->goodsModule->getKey($this->url);
        } else {
            return '';
        }
    }
}