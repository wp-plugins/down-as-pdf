<?php
//============================================================+
// File name   : tcpdf_config.php
// Begin       : 2004-06-11
// Last Update : 2011-04-15
//
// Description : Configuration file for TCPDF.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com s.r.l.
//               Via Della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//============================================================+

/**
 * Configuration file for TCPDF.
 * @author Nicola Asuni
 * @package com.tecnick.tcpdf
 * @version 4.9.005
 * @since 2004-10-27
 */


// Do not delete these lines,by 荒野无灯
if (!empty($_SERVER['SCRIPT_FILENAME']) && 'tcpdf_config.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not load this page directly. Thanks!');


// If you define the constant K_TCPDF_EXTERNAL_CONFIG, the following settings will be ignored.

if (!defined('K_TCPDF_EXTERNAL_CONFIG')) {

	//此全局变量在tcpdf.php line 23127 处理img标签时要用到
	// DOCUMENT_ROOT fix for IIS Webserver
	if ((!isset($_SERVER['DOCUMENT_ROOT'])) OR (empty($_SERVER['DOCUMENT_ROOT']))) {
		if(isset($_SERVER['SCRIPT_FILENAME'])) {
			$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['PHP_SELF'])));
		} elseif(isset($_SERVER['PATH_TRANSLATED'])) {
			$_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr(str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']), 0, 0-strlen($_SERVER['PHP_SELF'])));
		} else {
			// define here your DOCUMENT_ROOT path if the previous fails (e.g. '/var/www')
			$_SERVER['DOCUMENT_ROOT'] = '/';
		}
	}

	//利用wp常量，节省计算时间   default-constants.php
	//必须以 / 结尾 ！
	//by 荒野无灯	
	$k_path_main = WP_PLUGIN_DIR . '/down-as-pdf/tcpdf/';

	/**
	 * Installation path (/var/www/tcpdf/).
	 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
	 */
	define ('K_PATH_MAIN', $k_path_main);

	//利用wp常量，节省计算时间
	if (isset($_SERVER['HTTP_HOST']) AND (!empty($_SERVER['HTTP_HOST']))) {
		$k_path_url = WP_PLUGIN_URL .'/down-as-pdf/tcpdf/';
	}

	/**
	 * URL path to tcpdf installation folder (http://localhost/tcpdf/).
	 * By default it is automatically calculated but you can also set it as a fixed string to improve performances.
	 */
	define ('K_PATH_URL', $k_path_url);

	/**
	 * path for PDF fonts
	 * use K_PATH_MAIN.'fonts/old/' for old non-UTF8 fonts
	 */
	define ('K_PATH_FONTS', K_PATH_MAIN.'fonts/');

	/**
	 * cache directory for temporary files (full path)
	 *  缓存目录
	 */
	define ('K_PATH_CACHE',WP_PLUGIN_DIR . '/down-as-pdf/cache/');

	/**
	 * cache directory for temporary files (url path)
	 * 缓存URL   
	 */
	define ('K_PATH_URL_CACHE',WP_PLUGIN_URL . '/down-as-pdf/cache/');

	/**
	 *images directory
	 * 图片目录   
	 */
	define ('K_PATH_IMAGES', WP_PLUGIN_DIR . '/down-as-pdf/images/');

	/**
	 * blank image
	 */
	define ('K_BLANK_IMAGE', K_PATH_IMAGES.'_blank.png');

	/**
	 * page format
	 */
	define ('PDF_PAGE_FORMAT', 'A4');

	/**
	 * page orientation (P=portrait, L=landscape)
	 */
	define ('PDF_PAGE_ORIENTATION', 'P');

	/**
	 * document creator
	 */
	define ('PDF_CREATOR', 'TCPDF');

	/**
	 * document author
	 */
	define ('PDF_AUTHOR', 'TCPDF');

	/**
	 * header title
	 */
	define ('PDF_HEADER_TITLE', 'TCPDF Example');

	/**
	 * header description string
	 */
	define ('PDF_HEADER_STRING', "by 荒野无灯 - http://ihacklog.com\nadmin@ihacklog.com");

	/**
	 * image logo
	 */
	define ('PDF_HEADER_LOGO', 'logo.png');

	/**
	 * header logo image width [mm]
	 */
	define ('PDF_HEADER_LOGO_WIDTH', 40);

	/**
	 *  document unit of measure [pt=point, mm=millimeter, cm=centimeter, in=inch]
	 */
	define ('PDF_UNIT', 'mm');

	/**
	 * header margin
	 */
	define ('PDF_MARGIN_HEADER', 5);

	/**
	 * footer margin
	 */
	define ('PDF_MARGIN_FOOTER', 10);

	/**
	 * top margin
	 */
	define ('PDF_MARGIN_TOP', 27);

	/**
	 * bottom margin
	 */
	define ('PDF_MARGIN_BOTTOM', 25);

	/**
	 * left margin
	 */
	define ('PDF_MARGIN_LEFT', 15);

	/**
	 * right margin
	 */
	define ('PDF_MARGIN_RIGHT', 15);

	/**
	 * default main font name
	 */
	define ('PDF_FONT_NAME_MAIN', 'droidsansfallback');

	/**
	 * default main font size
	 * 字体大小来自插件配置选项   
	 */
	define ('PDF_FONT_SIZE_MAIN', $main_font_size);

	/**
	 * default data font name
	 */
	define ('PDF_FONT_NAME_DATA', 'droidsansfallback');

	/**
	 * default data font size
	 */
	$font_size_data= $main_font_size > 10 ? $main_font_size -2 : $main_font_size;    
	define ('PDF_FONT_SIZE_DATA', $font_size_data);

	/**
	 * default monospaced font name
	 */
	define ('PDF_FONT_MONOSPACED', 'dejavusansmono');

	/**
	 * ratio used to adjust the conversion of pixels to user units
	 */
	define ('PDF_IMAGE_SCALE_RATIO', 1.25);

	/**
	 * magnification factor for titles
	 */
	define('HEAD_MAGNIFICATION', 1.1);

	/**
	 * height of cell repect font height
	 */
	define('K_CELL_HEIGHT_RATIO', 1.25);

	/**
	 * title magnification respect main font size
	 */
	define('K_TITLE_MAGNIFICATION', 1.3);

	/**
	 * reduction factor for small font
	 */
	define('K_SMALL_RATIO', 2/3);

	/**
	 * set to true to enable the special procedure used to avoid the overlappind of symbols on Thai language
	 */
	define('K_THAI_TOPCHARS', true);

	/**
	 * if true allows to call TCPDF methods using HTML syntax
	 * IMPORTANT: For security reason, disable this feature if you are printing user HTML content.
	 */
	define('K_TCPDF_CALLS_IN_HTML', true);
}

//============================================================+
// END OF FILE
//============================================================+
