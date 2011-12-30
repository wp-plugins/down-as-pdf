<?php

/**
 * @filename FontConfig.class.php 
 * @encoding UTF-8 
 * @author 荒野无灯 <HuangYeWuDeng, admin@ihacklog.com> 
 * @link http://ihacklog.com 
 * @copyright Copyright (C) 2011 荒野无灯 
 * @license http://www.gnu.org/licenses/
 * @datetime Dec 30, 2011  6:40:47 PM
 * @version 1.0

 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * $Id$
 * $Revision$
 * $Date$
 * @package Down As PDF
 * @encoding UTF-8 
 * @author 荒野无灯 <HuangYeWuDeng> 
 * @link http://ihacklog.com 
 * @copyright Copyright (C) 2011 荒野无灯 
 * @license http://www.gnu.org/licenses/
 */

class DAP_LangFontConfig
{
	private $_config = array(
		'lang'=>'',
		'font'=>'droidsansfallback',
		'font_subsetting'=> FALSE,
	);
	
	public function __construct($font ='',$font_subsetting = FALSE)
	{
		switch (WPLANG)
		{
			case 'zh_CN':
				$this->_config['lang'] = "chi";
				$this->_config['font'] = "cid0cs";
				$this->_config['font_subsetting'] = TRUE;
				break;
			case 'zh_TW':
				$this->_config['lang'] = "zho";
				$this->_config['font'] = "cid0ct";
				$this->_config['font_subsetting'] = TRUE;
				break;
			case 'zh_HK':
				$this->_config['lang'] = "zho";
				$this->_config['font'] = "cid0ct";
				$this->_config['font_subsetting'] = TRUE;
				break;
			case 'ja';
				$this->_config['lang'] = "jpn";
				$this->_config['font'] = "cid0jp";
				$this->_config['font_subsetting'] = TRUE;
			case 'ko_KR':
				$this->_config['lang'] = "kor";
				$this->_config['font'] = "cid0kr";
				$this->_config['font_subsetting'] = TRUE;
				break;
			default:
				$this->_config['lang'] = "eng";
				$this->_config['font'] = "dejavusans";
				$this->_config['font_subsetting'] = FALSE;
				break;
		}
		if( !empty( $font ))
		{
			$this->_config = array_merge($this->_config, array('font'=> $font,'font_subsetting'=>$font_subsetting) );
		}
	}
	
	public function get($option)
	{
		switch($option)
		{
			case 'lang':
				return $this->_config['lang'];
				break;
			case 'font':
				return $this->_config['font'];
				break;
			case 'font_subsetting':
				return $this->_config['font_subsetting'];
				break;
			default:
				throw new Exception('Error param!');
		}
	}

}
