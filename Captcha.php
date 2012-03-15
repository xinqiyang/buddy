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
 * Captcha Calss
 * Captcha::instance('Captcha')->generate();
 * generate png pic ,size: 88 * 32
 * @author xinqiyang
 *
 */
class Captcha extends Base
{
  	/**
     * @var array Character sets
     */
    static $V = array("a", "e", "d", "u", "y");
    static $VN = array("a", "e", "d", "u", "y", "2", "3", "4", "5", "6", "7", 
    "8", "9");
    static $C = array("b", "c", "d", "f", "g", "h", "a", "k", "m", "n", "p", 
    "q", "r", "s", "t", "u", "v", "w", "x", "z");
    static $CN = array("b", "c", "d", "f", "g", "h", "a", "k", "m", "n", "p", 
    "q", "r", "s", "t", "u", "v", "w", "x", "z", "2", "3", "4", "5", "6", "7", 
    "8", "9");

    private $_arrOption = array(
        'fonts' => array('Candice.ttf','Ding-DongDaddyO.ttf','Duality.ttf','Jura.ttf'),
        'fontsize' => 24,
        'width' => 80,
        'height' => 40,
        'wordlen' => 4,
        'suffix' => '.png',
        'dotnoiselevel' => 100,
        'linenoiselevel' => 5,
        'usenumbers' => true,
        'degree' => array(- 6, 6, 10, - 10, 12, - 12, 7, - 7),
    );
    /**
     * construct
     *
     * @param array $arrOption
     */
    public function __construct ($arrOption='')
    {
    	if($arrOption)
    	{
        	$this->_arrOption = array_merge($this->_arrOption, array_intersect_key($arrOption,$this->_arrOption));
    	}
    }
    /**
     * Generate captcha
     *
     * @return string captcha ID
     */
    public function generate()
    {
        $strWord = $this->_generateWord();
        $this->_generateImage($strWord);
        //set the captcha session of the request
        Session::set('captcha', $strWord);
        return ;
    }
    /** 
     * generate word
     * @return string generate words
     */
    protected function _generateWord ()
    {
        $strWord = '';
        $intWordLen = $this->_arrOption['wordlen'];
        $arrVowels = $this->_arrOption['usenumbers'] ? self::$VN : self::$V;
        $arrConsonants = $this->_arrOption['usenumbers'] ? self::$CN : self::$C;
        for ($i = 0; $i < $intWordLen; $i = $i + 2) {
            // generate word with mix of vowels and consonants
            $strConsonants = $arrConsonants[array_rand($arrConsonants)];
            $strVowels = $arrVowels[array_rand($arrVowels)];
            $strWord .= $strConsonants . $strVowels;
        }
        if (strlen($strWord) > $intWordLen) {
            $strWord = substr($strWord, 0, $intWordLen);
        }
        return $strWord;
    }
    
    /**
     *
     * generate image
     * @param string $word Captcha word
     */
    protected function _generateImage ($strWord)
    {
        if (! extension_loaded("gd")) {
            throw new Exception(
            "Image CAPTCHA requires GD extension");
        }
        if (! function_exists("imagepng")) {
            throw new Exception(
            "Image CAPTCHA requires PNG support");
        }
        if (! function_exists("imageftbbox")) {
            throw new Exception(
            "Image CAPTCHA requires FT fonts support");
        }
        $strFont = dirname(__FILE__).DIRECTORY_SEPARATOR.'Resource/'.$this->_arrOption['fonts'][array_rand($this->_arrOption['fonts'])];
        if (empty($strFont)) {
            throw new Exception("Image CAPTCHA requires font");
        }
        $intWidth = $this->_arrOption['width'];
        $intHeight = $this->_arrOption['height'];
        $intFontSize = $this->_arrOption['fontsize'];
        $arrTextBox = imageftbbox($intFontSize, 0, $strFont, $strWord);
        //adjust the h according to textbox
        $intTextBoxWidth = abs($arrTextBox[2] - $arrTextBox[0]);
        $intTextBoxHeight = abs($arrTextBox[7] - $arrTextBox[1]);
        if ($intWidth < $intTextBoxWidth) {
            $intWidth = $intTextBoxWidth;
        }
        if ($intTextBoxWidth > 0) {
            $intHeight = ($intTextBoxHeight / $intTextBoxWidth) * $intWidth;
        }
        
        $resImage = imagecreatetruecolor($intWidth, $intHeight);
    
        $intTextColor = imagecolorallocate($resImage, 0, 0, 0);
        $intBgColor = imagecolorallocate($resImage, 255, 255, 255);
        
        imagefilledrectangle($resImage, 0, 0, $intWidth - 1, $intHeight - 1, $intBgColor);
        $intX = ($intWidth - $arrTextBox[2] - $arrTextBox[0]) / 2;
        //$intX = rand(3, 6);
        $intY = ($intHeight - $arrTextBox[7] - $arrTextBox[1]) / 2;
        $intWordLen = strlen($strWord);
        for ($i = 0; $i < $intWordLen; $i ++) {
            $strLetter = $strWord[$i];
            $intDegree = $this->_arrOption['degree'][array_rand($this->_arrOption['degree'])];

            $arrCoords = imagefttext($resImage, $intFontSize + 4, $intDegree, $intX, $intY, 
            $intTextColor, $strFont, $strLetter);
            $intX += ($arrCoords[2] - $intX) + (- 3);
          
        }
        //$resImage2 = $resImage;
        header("Content-type: image/" . str_replace('.', '', 
        $this->_arrOption['suffix']));
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // Date in the past
        imagepng($resImage);
        imagedestroy($resImage);
    }
  
}
