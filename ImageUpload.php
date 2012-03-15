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
 * Image Upload service
 * use upaiyun.com image service
 * 使用了又拍云存储的图片服务
 * @author xinqiyang
 *
 */
class ImageUpload
{
	protected $_config = array();
	public function __construct()
	{
		$this->_config = C('imageservice');
	}

	/**
	 * update file to upaiyun.com
	 * 上传图片保存到又拍云存储网
	 * @param string $object 对象
	 * @param string $filename  文件名称
	 * @param string $path  文件路径
	 */
	public function put($object,$filename,$path)
	{
		$object = empty($object) ? '' : $object.'/';
		$postField = file_get_contents((realpath($path)));
		$process = curl_init($this->_config['api'].'/'.$this->_config['bucketname'].'/'.$object.$filename);
		curl_setopt($process, CURLOPT_POST, 1);
		curl_setopt($process, CURLOPT_POSTFIELDS, $postField);
		curl_setopt($process, CURLOPT_USERPWD, $this->_config['username'].':'.$this->_config['userpass']);
		curl_setopt($process, CURLOPT_HTTPHEADER, array('Expect:', "Mkdir:true"));
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($process);
		$code = curl_getinfo($process, CURLINFO_HTTP_CODE);
		curl_close($process);
		return array('code'=>$code,'info'=>$result);
	}

	public  function get($object,$filename)
	{
		$object = empty($object) ? '' : $object.'/';
		$process = curl_init($this->_config['api'].'/'.$this->_config['bucketname'].'/'.$object.$filename);
		curl_setopt($process, CURLOPT_USERPWD, $this->_config['username'].':'.$this->_config['userpass']);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($process);
		$code = curl_getinfo($process, CURLINFO_HTTP_CODE);
		curl_close($process);
		return array('code'=>$code,'info'=>$result);
	}

	public  function delete($object,$filename)
	{
		$object = empty($object) ? '' : $object.'/';
		$process = curl_init($this->_config['api'].'/'.$this->_config['bucketname'].'/'.$object.$filename);
		curl_setopt($process, CURLOPT_CUSTOMREQUEST, 'DELETE');
		curl_setopt($process, CURLOPT_USERPWD, $this->_config['username'].':'.$this->_config['userpass']);
		curl_setopt($process, CURLOPT_HEADER, 0);
		curl_setopt($process, CURLOPT_TIMEOUT, 30);
		curl_setopt($process, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($process, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($process);
		$code = curl_getinfo($process, CURLINFO_HTTP_CODE);
		curl_close($process);
		return array('code'=>$code,'info'=>$result);
	}

	public  function usage()
	{
		 $process = curl_init($this->_config['api'].'/'.$this->_config['bucketname'].'?usage'); 
		 curl_setopt($process, CURLOPT_USERPWD, $this->_config['username'].':'.$this->_config['userpass']);
		 curl_setopt($process, CURLOPT_HEADER, 0); 
		 curl_setopt($process, CURLOPT_TIMEOUT, 30); 
		 curl_setopt($process, CURLOPT_RETURNTRANSFER, 1); 
		 $result = curl_exec($process);
		$code = curl_getinfo($process, CURLINFO_HTTP_CODE);
		curl_close($process);
		return array('code'=>$code,'info'=>$result);
	}
	
	/**
	 * save file or image to upaiyun.com then update db
	 * 先保存数据到又拍云存储，然后在更新数据库
	 * @param string $model
	 * @param string $filename   保存文件名
	 * @param mixed $streamFile  流文件或者是图片路径
	 * @param bigint $id ID
	 * @param bigint $user_id userid
	 */
	public  function save($account_id, $filename = '', $streamFile = '', $id = '')
	{
		$result = $this->put('image', $filename,$streamFile);
                logDebug('UPLOAD FILE TO UPAI '.  json_encode($result));
		$id = empty($id) ? objid() : $id;
		$filename = empty($filename) ? $id.'.jpg' : $filename;
		if($result['code'] == 200)
		{
			return ImageLogic::setImage($id,$account_id,$filename,'web');
		}
		logNotice(__CLASS__.'/'.__FUNCTION__.':UPLOAD IMAGE ERROR PATH:%s',$filename);
		return false;
	}
	
	
}
