<?php

/* =============================================================================
 * $Id$
 * $Revision$
 * $Date$
 * @package Down As PDF
 * @encoding UTF-8 
 * @author 荒野无灯 <HuangYeWuDeng> 
 * @link http://ihacklog.com 
 * @copyright Copyright (C) 2011 荒野无灯 
 * @license http://www.gnu.org/licenses/
 * Desc: 生成文章PDF文档
 * @TODO: 修正输出JSON,Javascript的bug,对过长标题做截断处理，代码颜色、背景自定义...
  ============================================================================= */

@error_reporting(0);
require_once "../../../wp-load.php";

if (headers_sent($file, $line))
{
	if (WP_DEBUG) {
		wp_die('Error: header already sent in file <strong>' . $file . '</strong> line <strong>' . $line . '</strong>.Please check your server configure or contact the administrator.');
	} else {
		wp_die(__('Error: header already sent! Please contact the site administrator to solve this problem.', self::textdomain));
	}
}

/**
 * antileech check
 * for that https does not send referer info
 */
if (!is_ssl() && (!isset($_SERVER ['HTTP_REFERER']) || $_SERVER ['HTTP_REFERER'] == '') ) {
	wp_die(__('Please do not leech.', hacklog_dap::plugin_domain));
}

$refererhost = @parse_url($_SERVER ['HTTP_REFERER']);
//如果本站下载也被误认为盗链，请修改下面www.your-domain.com为你的博客域名
$validReferer = array('www.your-domain.com', $_SERVER ['HTTP_HOST']);
if (!(in_array($refererhost ['host'], $validReferer))) {
	wp_die(__('Please do not leech.', hacklog_dap::plugin_domain));
}

/**
 * make sure the input data is secure.
 */
$post_id = 0;
$post_id = (int) $_GET['id'];
$objPost = get_post($post_id);
//check if post exists
if (!$objPost) {
	wp_die(__('OOPS!Post does not exists.', hacklog_dap::plugin_domain));
}

$down_as_pdf_options = get_option('down_as_pdf_options');
$download_type = stripslashes($down_as_pdf_options['download_type']);
$show_in = stripslashes($down_as_pdf_options['show_in']);
$main_font_size = stripslashes($down_as_pdf_options['main_font_size']);
$enable_font_subsetting = stripslashes($down_as_pdf_options['enable_font_subsetting']);
$use_cc = stripslashes($down_as_pdf_options['use_cc']);
//'droidsansfallback' OR 'stsongstdlight' OR msungstdlight
//$main_font = 'msungstdlight';
$main_font = 'droidsansfallback';
//only allowed post type can be downloaded
if (!in_array($objPost->post_type, array($show_in)) && 'post,page' != $show_in) {
	wp_die(__('Oh,No! What are U doing?', hacklog_dap::plugin_domain));
}


/**
 * function used to setup TCPDF local languages
 */
function dap_set_up_lang() {
	switch (WPLANG) {
		case 'zh_CN':
			$lang_file = 'chi';
			break;
		case 'zh_TW':
			$lang_file = 'zho';
			break;
		default:
			$lang_file = 'eng';
			break;
	}
	require_once hacklog_dap::get_plugin_dir(). "tcpdf/config/lang/{$lang_file}.php";
}

//setup the lang
dap_set_up_lang();
//require the LIB
require hacklog_dap::get_plugin_dir(). 'tcpdf/tcpdf.php';

//author
$objAuthor = get_userdata($objPost->post_author);
$strPermalink = get_permalink($objPost->ID);
$strShortlink =  wp_get_shortlink($objPost->ID);
$home_url = home_url('/');
$admin_email = get_option('admin_email');
if ($objAuthor->display_name) {
	$strAuthor = $objAuthor->display_name;
} else {
	$strAuthor = $objAuthor->user_nicename;
}
//标签  TAGS     
$t = array();
$tags = '';
$tags_arr = wp_get_post_tags($objPost->ID);
if ($tags_arr) {
	foreach ($tags_arr as $item) {
		$t[] = $item->name;
	}
	$tags = implode(',', $t);
}
// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8',
				false, false);
// set document information
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor($strAuthor . ' ' . $admin_email);
$pdf->SetTitle($objPost->post_title . get_option('blogname'));
$pdf->SetSubject(get_the_category_list(',', '', $post_id));
$pdf->SetKeywords($tags);
// set default header data
$max_title_len = 40;
$post_title = strip_tags($objPost->post_title);
$len = function_exists('mb_strlen') ? mb_strlen($post_title, 'UTF-8') : strlen($post_title);
$end_str = $len > $max_title_len ? '...' : '';
$part_title = function_exists('mb_substr') ? mb_substr($post_title, 0, $max_title_len, 'UTF-8') : substr($post_title, 0, $max_title_len);
$part_title .= $end_str;

$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, $part_title, "by {$strAuthor} - {$home_url}  {$admin_email}   date:" . date('Y-m-d'));
// set header and footer fonts
$pdf->setHeaderFont(Array(
	PDF_FONT_NAME_MAIN,
	'',
	10));
//if uses CJK ,be aware of the font. use Latin font ,the page footer char will become '??'
$pdf->setFooterFont(Array(
	PDF_FONT_NAME_MAIN,
	'',
	PDF_FONT_SIZE_DATA));
// set default monospaced font
//dejavusansmono is good for code
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
//set margins
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
//set auto page breaks
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
//set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
//set some language-dependent strings
$pdf->setLanguageArray($l);
// ---------------------------------------------------------
// Set font
// dejavusans is a UTF-8 Unicode font, if you only need to
// print standard ASCII chars, you can use core fonts like
// helvetica or times to reduce file size.
//SetFont($family, $style='', $size=0, $fontfile='', $subset='default')
//$pdf->SetFont('arialunicid0', '', 14, '', true);
//use font subsetting or not
$subsetting_value = 1 == $enable_font_subsetting ? true : false;
$pdf->setFontSubsetting($subsetting_value);
$pdf->SetFont($main_font, '', PDF_FONT_SIZE_MAIN, '', 'default');
//for Chinese word in pre tags
$pdf->SetDefaultMonospacedFont($main_font);
/*
 * By default TCPDF enables font subsetting to reduce the size of embedded Unicode TTF fonts,
 *  this process, that is very slow and requires a lot of memory, can be turned off using 
 *  setFontSubsetting(false) method;
 */
// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();
$content = $objPost->post_content;
//for codecolorer plugin
$content = preg_replace_callback(
		'#(\s*)\[cc([^\s\]_]*(?:_[^\s\]]*)?)([^\]]*)\](.*?)\[/cc\2\](\s*)#si', create_function('$matches', 'return $matches[1] ."<pre style=\"word-wrap:break-word;color: #406040;background-color: #F1F1F1;border: 1px solid #9F9F9F;\">". htmlspecialchars($matches[4]) ."</pre>" . $matches[5];'), $content);

//for code tag
$content = preg_replace_callback(
		'#(\s*)\<code(.*?)\>(.*?)\</code\>(\s*)#si', create_function('$matches', 'return $matches[1] ."<pre style=\"word-wrap:break-word;color: #406040;background-color: #F1F1F1;border: 1px solid #9F9F9F;\">". htmlspecialchars($matches[3]) ."</pre>" . $matches[4];'), $content);

//for wp-syntax plugin pre tag
$content = preg_replace_callback(
		"/(\s*)<pre(?:lang=[\"']([\w-]+)[\"']|line=[\"'](\d*)[\"']|escaped=[\"'](true|false)?[\"']|\s)+>(.*)<\/pre>(\s*)/siU", create_function('$matches', 'return $matches[1] ."<pre style=\"word-wrap:break-word;color: #406040;background-color: #F1F1F1;border: 1px solid #9F9F9F;\">". htmlspecialchars($matches[5]) ."</pre>" . $matches[6];'), $content
);
//blockquote  #F0F0F0  #F5F5F5; border: 1px solid #DADADA; color:#555555;
$content = preg_replace_callback(
		"/(\s*)<blockquote\s*>(.*)<\/blockquote>(\s*)/siU", create_function('$matches', 'return $matches[1] ."<pre style=\"word-wrap:break-word;color:#000000;background-color: #F5F5F5;border: 1px solid #DADADA;\">". htmlspecialchars($matches[2]) ."</pre>" . $matches[3];'), $content
);
$postOutput = $content;
//	$postOutput = apply_filters('the_content',$content);
//	$postOutput = preg_replace('/<img[^>]+./','', $content);
// add a page
// ---------------------------------------------------------

$html_title = '<h1 style="text-align:center;">' . $objPost->post_title . '</h1>';
//$pdf->writeHTMLCell(0, 0, '', '', $html_title, 0, 0, 0, true, 'C', true);
$html_author = '<strong style="text-align:right;">' . $objPost->post_date . ' By ' . $strAuthor . '</strong>';
//$pdf->writeHTMLCell(0, 0, '', '', $html_author, 0, 0, 0, false, 'R', true);
//$strHtml = wpautop( $html_title. $html_author .'<br/><br/>' . $postOutput .'<br/><br/>', true);
/**
 * @todo to this in a replace callback function,below code may cause the content changed.
 */
$strHTML = str_replace(array('<br/><br/>', '<br/><br/><br/>', '<br/><br/><br/><br/>'), array('<br/>', '<br/>', '<br/>'), $strHTML);
$strHtml = $html_title . $html_author . '<br/><br/>' . $postOutput . '<br/><br/>';

// Print text using writeHTMLCell()
$pdf->writeHTMLCell($w = 0, $h = 0, $x = '', $y = '', $strHtml, $border = 0, $ln = 1, $fill = 0, $reseth = true, $align = '', $autopadding = true);

//Creative Commons Attribution-NonCommercial-ShareAlike 2.5 Generic License
$copy_right = $use_cc ? sprintf(__('<h2 style="color:red;"><strong>Copyright</strong> :</h2>All this contents are published under <a style="color:red;" href="http://creativecommons.org/licenses/by-nc-sa/2.5/" target="_blank">Creative Commons Attribution-NonCommercial-ShareAlike 2.5 Generic License</a>. <br />for reproduced, please specify from this website <a  style="color:green;" target="_blank" href="%s"><strong>%s</strong></a> AND give the URL.<br />Article link：<a href="%s">%s</a><br/>',  hacklog_dap::plugin_domain),home_url('/'),get_bloginfo('name'),$strPermalink,$strShortlink ) : '';

if ('' != $copy_right) 
{
	//cc
	// set color for background
	$pdf->SetFillColor(255, 255, 127);
	$pdf->setCellPaddings(5, 5, 0, 0); //L T R B
	//$pdf->writeHTMLCell($w=0, $h=0, $x='', $y='', $txt, $border=0, $ln=1, $fill=0, $reseth=true, $align='C', $autopadding=true);
	$pdf->MultiCell(180, 5, $copy_right . "\n", 1, 'L', 1, 2, '', '', true, 0, true);
	// MultiCell($w, $h, $txt, $border=0, $align='J', $fill=0, $ln=1, $x='', $y='', $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0)
}
// ---------------------------------------------------------
// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output($objPost->post_name . '.pdf', $download_type);
// End of file