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
 * RSS output
 * $rss = new Rss();
 * $rss->outPut($data);
 * @author xinqiyang
 *
 */
class Rss
{
	private $version = 'rss20';
	private $items = '';
	private $rss = '';

	/**
	 * outPut the rss
	 * $array['site'] = array('title'=>'','desc'=>'','url'=>'')
	 * $array['body'] = array( array('topic'=>'','link'=>'','date'=>'','abstract'=>'','author'=>'') )
	 * @param array $itemrray the data prepair for output as rss format
	 */
	public function outPut($array)
	{
		if(!empty($array) && is_array($array)){
			//add data 
			foreach($array['body'] as $value)
			{
				$this->addItem($value);
			}
			$this->outRss($array['site']) ;
			//echo the rss of the 
			header("Content-Type:text/xml; charset=utf-8");
            exit($this->rss);
		}
	}

	/**
	 * set rss version of the Rss output
	 * @param string $version rss version
	 */
	public function rssVer($version='')
	{
		$this->version = empty($version) ? 'rss20' : $version;
	}

	private function strip($s)
	{
		$s = str_replace('&', '&amp;', $s);
		$s = str_replace('<', '&lt;', $s);
		$s = str_replace('>', '&gt;', $s);
		if ('' == $s)
		{
			$s = ' ';
		}
		return $s;
	}

	private function addItem($array)
	{
		switch ($this->version)
		{
			case 'rss092':
				$item = "\t" . '<item>' . "\n";
				$item .= "\t\t" . '<title>' . $this->strip($array['topic']) . '</title>                             ' . "\n";
				$item .= "\t\t" . '<description><![CDATA[' . $array['abstract'] . ']]></description>' . "\n";
				$item .= "\t\t" . '<link>' . $this->strip($array['link']) . '</link>' . "\n";
				$item .= "\t" . '</item>' . "\n";
				break;
			case 'rss10':
				$item = "\t" . '<item rdf:about="' . $this->strip($array['link']) . '">' . "\n";
				$item .= "\t\t" . '<title>' . $this->strip($array['topic']) . '</title>' . "\n";
				$item .= "\t\t" . '<dc:title>' . $this->strip($array['topic']) . '</dc:title>' . "\n";
				$item .= "\t\t" . '<description><![CDATA[' . $array['abstract'] . ']]></description>' . "\n";
				$item .= "\t\t" . '<link>' . $this->strip($array['link']) . '</link>' . "\n";
				$item .= "\t\t" . '<dc:date>' . substr($array['date'], 0, 4) . '-' . substr($array['date'], 4, 2) . '-' . substr($array['date'], 6, 2) . ' ' . substr($array['date'], 8, 2) . ':' . substr($array['date'], 10, 2) . ':' . substr($array['date'], 12, 4) . '</dc:date>' . "\n";
				$item .= "\t\t" . '<dc:creator>' . $this->strip($array['author']) . '</dc:creator>' . "\n";
				$item .= "\t" . '</item>' . "\n";
				break;
			case 'rss20':
				$item = "\t" . '<item>' . "\n";
				$item .= "\t\t" . '<title>' . $this->strip($array['topic']) . '</title>' . "\n";
				$item .= "\t\t" . '<link>' . $this->strip($array['link']) . '</link>' . "\n";
				$item .= "\t\t" . '<pubDate>' . $array['date'] . ' GMT</pubDate>' . "\n";
				$item .= "\t\t" . '<description><![CDATA[' . $array['abstract'] . ']]></description>' . "\n";
				$item .= "\t\t" . '<author>' . $this->strip($array['author']) . '</author>' . "\n";
				$item .= "\t" . '</item>' . "\n";
				break;
			case 'atom':
				$item = "\t" . '<entry>' . "\n";
				$item .= "\t\t" . '<title>' . $this->strip($array['topic']) . '</title>' . "\n";
				$item .= "\t\t" . '<link>' . $this->strip($array['link']) . '</link>' . "\n";
				$item .= "\t\t" . '<created>' . substr($array['date'], 0, 4) . '-' . substr($array['date'], 4, 2) . '-' . substr($array['date'], 6, 2) . 'T' . substr($array['date'], 8, 2) . ':' . substr($array['date'], 10, 2) . ':' . substr($array['date'], 12, 4) . '</created>' . "\n";
				$item .= "\t\t" . '<summary><![CDATA[' . $array['abstract'] . ']]></summary>' . "\n";
				$item .= "\t\t" . '<author>' . "\n";
				$item .= "\t\t\t" . '<name>' . $this->strip($array['author']) . '</name>' . "\n";
				$item .= "\t\t" . '</author>' . "\n";
				$item .= "\t\t" . '<dc:subject>' . $this->strip($array['topic']) . '</dc:subject>' . "\n";
				$item .= "\t\t" . '<content><![CDATA[' . $array['detail'] . ']]></content>' . "\n";
				$item .= "\t" . '</entry>' . "\n";
				break;
		}
		$this->items .= $item;
	}

	private function outRss($body,$encoding='utf-8')
	{
		$this->rss = '<?xml version="1.0" encoding="'.$encoding.'"?>' . "\n";
		switch ($this->version)
		{
			case 'rss092':
				$this->rss .= '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://my.netscape.com/rdf/simple/0.9/">' . "\n";
				$this->rss .= '<channel>' . "\n";
				$this->rss .= "\t" . '<title>' . $this->strip($body[0]) . '</title>' . "\n";
				$this->rss .= "\t" . '<description><![CDATA[' . $body[1] . ']]></description>' . "\n";
				$this->rss .= "\t" . '<link>' . $this->strip($body[2]) . '</link>' . "\n";
				$this->rss .= '</channel>' . "\n";
				$this->rss .= $this->items;
				$this->rss .= '</rdf:RDF>';
				break;
			case 'rss10':
				$this->rss .= '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns="http://purl.org/rss/1.0/" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
				$this->rss .= '<channel>' . "\n";
				$this->rss .= "\t" . '<title>' . $this->strip($body[0]) . '</title>' . "\n";
				$this->rss .= "\t" . '<description><![CDATA[' . $body[1] . ']]></description>' . "\n";
				$this->rss .= "\t" . '<link>' . $this->strip($body[2]) . '</link>' . "\n";
				$this->rss .= $this->items;
				$this->rss .= '</channel>' . "\n";
				$this->rss .= '</rdf:RDF>' . "\n";
				break;
			case 'rss20':
				$this->rss .= '<rss version="2.0">' . "\n";
				$this->rss .= '<channel>' . "\n";
				$this->rss .= "\t" . '<title>' . $this->strip($body['title']) . '</title>' . "\n";
				$this->rss .= "\t" . '<description><![CDATA[' . $body['desc'] . ']]></description>' . "\n";
				$this->rss .= "\t" . '<link>' . $this->strip($body['url']) . '</link>' . "\n";
				$this->rss .= "\t" . '<lastBuildDate>' . date('D, d M Y H:i:s', time()) . ' GMT</lastBuildDate>' . "\n";
				$this->rss .= "\t" . '<language>'.$encoding.'</language>' . "\n";
				$this->rss .= "\t" . '<copyright>buddy framework rss generator</copyright>' . "\n";
				$this->rss .= $this->items;
				$this->rss .= '</channel>' . "\n";
				$this->rss .= '</rss>' . "\n";
				break;
			case 'atom':
				$this->rss .= '<feed version="0.3" xmlns="http://purl.org/atom/ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xml:lang="zh-cn">' . "\n";
				$this->rss .= "\t" . '<title>' . $this->strip($body[0]) . '</title>' . "\n";
				$this->rss .= "\t" . '<link>' . $this->strip($body[2 ]) . '</link>' . "\n";
				$this->rss .= "\t" . '<modified>' . substr($body[3], 0, 4) . '-' . substr($body[3], 4, 2) . '-' . substr($body[3], 6, 2) . 'T' . substr($body[3], 8, 2) . ':' . substr($body[3], 10, 2) . ':' . substr($body[3], 12, 4) . '</modified>' . "\n";
				$this->rss .= "\t" . '<tagline><![CDATA[' . $body[1] . ']]></tagline>' . "\n";
				$this->rss .= "\t" . '<generator>Blogchin RSSFeed Generator</generator>' . "\n";
				$this->rss .= "\t" . '<copyright>Copyright (C); 2004, BlogChina.com</copyright>' . "\n";
				$this->rss .= $this->items;
				$this->rss .= '</feed>' . "\n";
				break;
		}
	}
}