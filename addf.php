<?php

/**
 * $Id$
 * $Revision$
 * $Date$
 * @filename addf.php 
 * @encoding UTF-8 
 * @author 荒野无灯 <HuangYeWuDeng, admin@ihacklog.com> 
 * @link http://ihacklog.com 
 * @copyright Copyright (C) 2011 荒野无灯 
 * @license http://www.gnu.org/licenses/
 * @datetime Dec 31, 2011  11:16:34 AM
 * @version 1.0
 * @Description
 * page for add font
 * 
  */

require_once dirname(__FILE__) . '/../../../wp-load.php';

if (! current_user_can('manage_options') )
{
	wp_die(__('Permission denied!Please log in as Administrator.'));
}

$font = isset($_GET['font']) ? trim($_GET['font']): '';
if( empty( $font ))
{
	wp_die(__('<strong>font</strong> param is required!For example: ?font=the-font-name.ttf'));
}

//require the LIB
require hacklog_dap::get_plugin_dir() . 'tcpdf/tcpdf.php';
// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, TRUE, 'UTF-8',FALSE, FALSE);

$cache_dir =hacklog_dap::get_plugin_dir(). 'cache';
$file_path = $cache_dir .'/'. $font;
if( !is_file( $file_path ))
{
	wp_die(sprintf(__('Error: font file <strong>%s</strong> not exists!Pllease upload it to the dir:<strong>%s</strong>'),$file_path,$cache_dir) );
}
//function addTTFfont($fontfile, $fonttype='', $enc='', $flags=32, $outpath='')
//TrueTypeUnicode 
$fontname = $pdf->addTTFfont( $file_path , '', '', 32);

if( $fontname )
{
	wp_die(sprintf(__('<p>Good luck,the font <strong>%s</strong> has been added!</p>'),$fontname) );
}