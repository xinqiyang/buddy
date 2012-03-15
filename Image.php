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
 * Images Class
 * @author xinqiyang
 *
 */
class Image extends Base
{
	var $_img;
	var $_imagetype;
	var $_width;
	var $_height;
	
	/**
	 * resize image 
	 * 
	 * @param string $imgUrl image path
	 * @param string $model  model
	 * @param bigint $id  image id
	 * @param string $type image type
	 */
	public static function resizeImg($imgUrl,$model,$id,$type='s',$node='image')
	{
		$instance = self::instance(__CLASS__);
		$instance->load($imgUrl);
		$arr = C("image.$node");
		foreach ($arr[$type] as $val)
		{
			$instance->resize($val, 0,0);
			$savePath = $arr['path'].$model.DIRECTORY_SEPARATOR.''.$val.DIRECTORY_SEPARATOR.''.$id.'.jpg';
			$instance->save($savePath);
		}
		return true;
	}
	
	/**
	 * local image cut
	 * @param string $imgUrl url
	 * @param string $savePath save path
	 * @param int $width  width of pic
	 * @param int $height height of pic
	 * @param int $x  point x
	 * @param int $y  point y
	 */
	public static function cutImg($imgUrl,$savePath,$width=100,$height=100,$x=0,$y=0)
	{
		$instance = self::instance(__CLASS__);
		$instance->load($imgUrl);
		$instance->cut($width, $height, $x, $y);
		return $instance->save($savePath);
	}

	/**
	 * load image
	 * @param string $img_name imagepath
	 * @param string $img_type imagetype
	 */
	protected  function load($img_name, $img_type=''){
		if(!empty($img_type)) $this->_imagetype = $img_type;
		else $this->_imagetype = $this->get_type($img_name);
		switch ($this->_imagetype){
			case 'gif':
				if (function_exists('imagecreatefromgif'))	$this->_img=imagecreatefromgif($img_name);
				break;
			case 'jpg':
				$this->_img=imagecreatefromjpeg($img_name);
				break;
			case 'png':
				$this->_img=imagecreatefrompng($img_name);
				break;
			default:
				$this->_img=imagecreatefromstring($img_name);
				break;
		}
		$this->getxy();
	}

	/**
	 * do resize image
	 * @param int $width width of image
	 * @param int $height  height of image
	 * @param int $percent percent of the image
	 */
	protected  function resize($width, $height, $percent=0)
	{
		if(!is_resource($this->_img)) return false;
		if(empty($width) && empty($height)){
			if(empty($percent)) return false;
			else{
				$width = round($this->_width * $percent);
				$height = round($this->_height * $percent);
			}
		}elseif(empty($width) && !empty($height)){
			$width = round($height * $this->_width / $this->_height );
		}else{
			$height = round($width * $this->_height / $this->_width);
		}
		$tmpimg = imagecreatetruecolor($width,$height);
		if(function_exists('imagecopyresampled')) imagecopyresampled($tmpimg, $this->_img, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
		else imagecopyresized($tmpimg, $this->_img, 0, 0, 0, 0, $width, $height, $this->_width, $this->_height);
		$this->destroy();
		$this->_img = $tmpimg;
		$this->getxy();
	}

	/**
	 * cut image
	 * @param int $width width
	 * @param int $height height
	 * @param int $x  point x
	 * @param int $y  point y
	 */
	protected  function cut($width, $height, $x=0, $y=0){
		if(!is_resource($this->_img)) return false;
		if($width > $this->_width) $width = $this->_width;
		if($height > $this->_height) $height = $this->_height;
		if($x < 0) $x = 0;
		if($y < 0) $y = 0;
		$tmpimg = imagecreatetruecolor($width,$height);
		imagecopy($tmpimg, $this->_img, 0, 0, $x, $y, $width, $height);
		$this->destroy();
		$this->_img = $tmpimg;
		$this->getxy();
	}

    /**
     * display cut/resize result
     * @param bool $destroy  distory resource
     */
	public function display($destroy=true)
	{
		if(!is_resource($this->_img)) return false;
		switch($this->_imagetype){
			case 'jpg':
			case 'jpeg':
				header("Content-type: image/jpeg");
				imagejpeg($this->_img);
				break;
			case 'gif':
				header("Content-type: image/gif");
				imagegif($this->_img);
				break;
			case 'png':
			default:
				header("Content-type: image/png");
				imagepng($this->_img);
				break;
		}
		if($destroy) $this->destroy();
	}

	/**
	 * do save
	 * @param string $fname filename
	 * @param bool $destroy destory resource
	 * @param string $type  type
	 */
	public function save($fname, $destroy=false, $type='')
	{
		if(!is_resource($this->_img)) return false;
		if(empty($type)) $type = $this->_imagetype;
		switch($type){
			case 'jpg':
			case 'jpeg':
				$ret=imagejpeg($this->_img, $fname,100);
				break;
			case 'gif':
				$ret=imagegif($this->_img, $fname);
				break;
			case 'png':
			default:
				$ret=imagepng($this->_img, $fname,0);
				break;
		}
		if($destroy) $this->destroy();
		return $ret;
	}

	/**
	 * distory resource
	 */
	protected  function destroy()
	{
		if(is_resource($this->_img)) imagedestroy($this->_img);
	}

	/**
	 * get point x and y of image
	 */
	protected  function getxy()
	{
		if(is_resource($this->_img)){
			$this->_width = imagesx($this->_img);
			$this->_height = imagesy($this->_img);
		}
	}


	/**
	 * get image type
	 * @param string $img_name image name
	 */
	protected  function get_type($img_name)
	{
		if (preg_match("/\.(jpg|jpeg|gif|png)$/i", $img_name, $matches)){
			$type = strtolower($matches[1]);
		}else{
			$type = "string";
		}
		return $type;
	}
}