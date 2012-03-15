<?php
if (! defined("TOP_SDK_WORK_DIR")) {
    define("TOP_SDK_WORK_DIR", "taobao/");
}
class taobaoGoods implements InterGaceGoods
{
    private static $_objSelf;
    private static $appkey = '';
    private static $secretKey = '';
	private static $pid = '';
    private static $site = 'taobao';
    public function __construct ($arrConfig = array())
    {
        if (! empty($arrConfig)) {
            self::$appkey = $arrConfig['appkey'];
            self::$secretKey = $arrConfig['secretKey'];
			self::$pid = $arrConfig['pid'];
        }
    }
    public function getGoods ($url)
    {
        //商品基本信息
        $id = $this->getId($url);
        $topClient = new TopClient();
        $topClient->appkey = self::$appkey;
        $topClient->secretKey = self::$secretKey;
        $itemReq = new ItemGetRequest();
        $itemReq->setFields("detail_url,nick,props_name,num_iid,title,num_iid,input_str,pic_url,location,price,item_img,prop_img");
        $itemReq->setNumIid($id);
        $objResult = $topClient->execute($itemReq);
        if (! isset ($objResult->item))
        {
            return false;
        }
        $arrItem = (array)$objResult->item;
		$goodsInfo = array();
		if(empty($arrItem['num_iid']) || empty($arrItem['title']))
		{
			return false;
		}
		$goodsInfo['goods_name'] = $arrItem['title'];
		$goodsInfo['goods_url']  = $arrItem['detail_url'];
		$goodsInfo['goods_site'] = self::$site;
		$goodsInfo['nick'] = $arrItem['nick'];
		
		$goodsInfo['goods_price'] = $arrItem['price'];
		$goodsInfo['goods_brand'] = $arrItem['props_name'];
		
		$goodsInfo['goods_pic']   = $arrItem['pic_url'];
		$goodsInfo['goods_tags']  = $arrItem['input_str'];
		$goodsInfo['item_img']    = isset($arrItem['item_imgs']) ? $arrItem['item_imgs'] : '';
		$goodsInfo['prop_img']    = isset($arrItem['prop_imgs']) ? $arrItem['prop_imgs'] : '';
		$goodsInfo['goods_key']   = $this->getKey($url);
        //淘宝信息
        $goodsInfo['taoke_url'] = '';
        $goodsInfo['shopclickurl'] = '';
        $goodsInfo['seller_credit_score'] = '';
		if(!empty(self::$pid))
        {
            $objTaoke = new TaobaokeItemsDetailGetRequest();
            $objTaoke->setFields("click_url,shop_click_url,seller_credit_score");
            $objTaoke->setNumIids($id);
            $objTaoke->setPid(self::$pid);
            $objResult = $topClient->execute($objTaoke);
            if(isset($objResult->taobaoke_item_details))
			{
                $arrTaoke = (array)$objResult->taobaoke_item_details->taobaoke_item_detail;
                if(!empty($arrTaoke['click_url'])) {
                    $goodsInfo['taoke_url'] = $arrTaoke['click_url'];
				}

                if(!empty($arrTaoke['shop_click_url'])) {
                    $goodsInfo['shopclickurl'] = $arrTaoke['shop_click_url'];
				}
				
			    if(!empty($arrTaoke['shop_click_url'])) {
                    $goodsInfo['seller_credit_score'] = $arrTaoke['seller_credit_score'];
				}
            }
        }
        //店铺信息
        $goodsInfo['shop_name'] = '';
        if (!empty($arrItem['nick'])) {
            $objShop = new ShopGetRequest();
            $objShop->setFields("sid,cid,title,nick,desc,bulletin,pic_path,created,modified");
            $objShop->setNick($arrItem['nick']);
            $objResult = $topClient->execute($objShop);
            if (isset ($objResult->shop)) {
                $arrShop = (array)$objResult->shop;
                if (!empty($arrShop['title'])) {
                    $goodsInfo['shop_name'] = $arrShop['title'];
                }
            }
        }
        return $goodsInfo;
    }
    public function getId ($url)
    {
        $id = 0;
        $parse = parse_url($url);
        if (isset($parse['query'])) {
            parse_str($parse['query'], $params);
            if (isset($params['id']))
                $id = $params['id'];
        }
        return $id;
    }
    public function getKey ($url)
    {
        $id = $this->getID($url);
        return self::$site . $id;
    }
}