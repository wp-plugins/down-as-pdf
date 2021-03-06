<?php
/*
  Plugin Name: Hacklog Down As PDF
  Plugin URI: http://ihacklog.com/?p=3771
  Description: This plugin generates PDF documents for visitors when you click the "<strong>Download as PDF</strong>" button below the post. Very useful if you plan to share your posts in PDF format.You can replace the logo file <strong>logo.png</strong>under <strong>wp-content/plugins/down-as-pdf/images/</strong> with your own.
  Author: 荒野无灯
  Version: 2.3.6
  Author URI: http://ihacklog.com
 */


/**
 * $Id$
 * $Revision$
 * $Date$
 * @package Down As PDF
 * @encoding UTF-8 
 * @author 荒野无灯 <HuangYeWuDeng> 
 * @link http://ihacklog.com 
 * @copyright Copyright (C) 2012 荒野无灯 
 * @license http://www.gnu.org/licenses/
 */

/**
 * @TODO custom fonts add @see http://www.tcpdf.org/fonts.php
 * @TODO TCPDF uses cURL to get remote server images,if have time,modify it to WP HTTP lib
 * @TODO add PDF_IMAGE_SCALE_RATIO option (default is 1.25 now)
 * @TODO HTML table caption bug (TCPDF)
 * @todo switch between Text logo and Image logo,and make logo configurable
 * @todo make license content option configurable
 */

class hacklog_dap {
	const plugin_domain = 'hacklog-down-as-pdf';
	const meta_key = '_hacklog_down_as_pdf';
	const short_code = 'pdf_here';
	const opt_name = 'hacklog_down_as_pdf_options';
	const default_font = 'droidsansfallback';
	private static $plugin_dir = '';
	private static $allow_down_default = 1;

	public static function init() 
	{
		self::$plugin_dir = WP_PLUGIN_DIR . '/down-as-pdf/';
		add_action('init', array(__CLASS__, 'load_domain'));
		// Hook the admin_menu display to add admin page
		add_action('admin_menu', array(__CLASS__, 'settings_menu'));
		register_activation_hook(__FILE__, array(__CLASS__, 'activate_hook'));
		register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate_hook'));
		add_action('dap_clear_cache_daily_event', array(__CLASS__, 'clear_cache_daily'));
		//link in post to generate PDF
		add_filter('the_content', array(__CLASS__, 'add_link'));
		add_action('wp_head', array(__CLASS__, 'custom_css'));
		add_action('admin_notices', array(__CLASS__, 'admin_notice'));
		if( ! self::$allow_down_default )
		{
			add_action ( 'admin_menu', array(__CLASS__, 'create_meta_box') );
			add_action ( 'save_post', array(__CLASS__, 'save_custom_fields'), 1, 2 );
		}
//		add_shortcode( 'pdf_here',array(__CLASS__, 'parse_short_code') );
	}
	
	public static function get_allow_down_default()
	{
		return self::$allow_down_default;
	}
	
	public static function get_plugin_dir()
	{
		return self::$plugin_dir;
	}

	/**
	 * type => name
	 * @return type 
	 */
	public static function get_default_fonts()
	{
		return array(
		'droidsansfallback'=>'droidsansfallback',
			'msungstdlight'=>'msungstdlight',
		   'stsongstdlight'=>'stsongstdlight',
			       'cid0cs'=>'cid0-Simplified Chinese',
				   'cid0ct'=>'cid0-Traditional Chinese',
				   'cid0jp'=>'cid0-Japanese',
				   'cid0kr'=>'cid0-Korean',
			   'dejavusans'=>'DejaVu Sans',
				  'courier'=>'Courier',
				'helvetica'=>'Helvetica',
		   		    'times'=>'Times New Roman',
		);
	}

	public static function admin_notice() {
		if (substr($_SERVER["PHP_SELF"], -11) == 'plugins.php' && function_exists("admin_url") && !self::is_writeable_ACLSafe(self::$plugin_dir . 'cache')) {
			echo '<div class="error"><p><strong>' . sprintf(__("Error:%s is not writable!", self::plugin_domain),self::$plugin_dir . 'cache') . '</strong></p></div>';
		}
	}

// from legolas558 d0t users dot sf dot net at http://www.php.net/is_writable
	public static function is_writeable_ACLSafe($path) {
		// PHP's is_writable does not work with Win32 NTFS
		if ($path{strlen($path) - 1} == '/') // recursively return a temporary file path
			return self::is_writeable_ACLSafe($path . uniqid(mt_rand()) . '.tmp');
		else if (is_dir($path))
			return self::is_writeable_ACLSafe($path . '/' . uniqid(mt_rand()) . '.tmp');
		// check tmp file for read/write capabilities
		$rm = file_exists($path);
		$f = @fopen($path, 'a');
		if ($f === false)
			return false;
		fclose($f);
		if (!$rm)
			unlink($path);
		return true;
	}

	public static function activate_hook() {
		if (!self::is_writeable_ACLSafe(self::$plugin_dir . 'cache')) {
			wp_die(__(sprintf("Error:%s is not writable!", self::$plugin_dir . 'cache'), self::plugin_domain));
		}
		$default_options = array(
			'linktext' => __('Download as PDF', self::plugin_domain),
			'download_type' => 'D',
			'show_in' => 'post',
			'main_font_size' => 11,
			'enable_font_subsetting' => 1,
			'font'=>self::default_font,
			'use_cc' => 1,
			'cache' => 0,
		);
		add_option( self::opt_name, $default_options);
		//scheduly clear cache file
		wp_schedule_event(time(), 'daily', 'dap_clear_cache_daily_event');
	}

	public static function parse_short_code($attrs)
	{
	}
	
	public static function create_meta_box() {
		$content_types_array = array('post','page');
		foreach ( $content_types_array as $content_type ) {
			add_meta_box ( 'downadpdf-custom-fields', _('Hacklog Down as PDF'),array(__CLASS__,'print_custom_fields'), $content_type, 'side', 'low', $content_type );
	}
	}


public static function print_custom_fields($post, $callback_args = '') 
		{
		$content_type = $callback_args ['args']; // the 7th arg from add_meta_box()
		$output = '';
		$value = get_post_meta($post->ID, self::meta_key,TRUE);
		$data = array (
			'name' => self::meta_key, 
			'title' => __('is this post allowed to Down as PDF ?',self::plugin_domain), 
			'description' => __('allow down as PDF',self::plugin_domain),  
			'options' => array ( array('title'=>'No','value'=>0),array('title'=>'Yes','value'=>1))
		);
		$option_str = '';
		foreach ( $data['options'] as $option ) {
			$option['value'] = _wp_specialchars( $option['value']); // Filter the values
			$is_selected = '';
			if ($value == $option['value']) {
				$is_selected = 'selected="selected"';
			}
			$option_str .= '<option value="' . $option['value'] . '" ' . $is_selected . '>' . $option['title'] . '       </option>';
		}
		$output_this_field = sprintf('<label for="%s"><strong>%s</strong></label><br/>
			<select name="%s" id="%s">
			%s 
			</select>',$data['name'],$data['title'],$data['name'],$data['name'],$option_str);
	
			// optionally add description
			if ($field ['description']) {
				$output_this_field .= '<p>' . $field ['description'] . '</p>';
			}
			
			$output .= '<div class="form-field form-required">' . $output_this_field . '</div>';
		// Print the form
		echo '<div class="form-wrap">';
		//http://codex.wordpress.org/Function_Reference/wp_nonce_field
//		wp_nonce_field ( 'update_down_as_pdf_fields', 'down_as_pdf_fields_nonce' );
		echo $output;
		echo '</div>';
	
	}


public static function save_custom_fields($post_id, $post) {
				$custom_fields = array('_down_as_pdf');
				foreach ( $custom_fields as $field ) {
					if (isset ( $_POST [$field] )) {
						$value = trim ( $_POST [$field] );
						update_post_meta ( $post_id, $field, $value );
					} // if not set, then it's an unchecked checkbox, so blank out the value.
				else {
						update_post_meta ( $post_id, $field, '' );
					}
				}
}
	public static function clear_cache_daily() 
	{
		$cache_dir = self::$plugin_dir . 'cache';
		// do something every day
		if (is_dir($cache_dir)) 
		{
			if ( ($handle = @opendir($cache_dir)) != FALSE )
			{
				while (false !== ( $file = readdir($handle))) {
					if ('.' != $file && '..' != $file && !is_dir($file)) {
						@unlink($cache_dir .'/'. $file);
					}
				}
				closedir($handle);
			}
		}
	}
	
	public static function format_size($size) 
	{
      $sizes = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
      if ($size == 0) { return('n/a'); } else {
      return (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i]); }
	}
	public static function get_cache_size()
	{
		$cache_dir = self::$plugin_dir . 'cache';
		is_dir( $cache_dir ) || mkdir($cache_dir,0777);
		$size = 0;
		// do something every day
		if (is_dir($cache_dir)) 
		{
			if ( ($handle = @opendir($cache_dir)) != FALSE )
			{
				while (false !== ( $file = readdir($handle))) 
				{
					if ('.' != $file && '..' != $file && !is_dir($file)) 
					{
						$size += filesize($cache_dir .'/'. $file);
					}
				}
				closedir($handle);
			}
		}
		return $size;
	}

	public static function deactivate_hook() {
		delete_option(self::opt_name);
		wp_clear_scheduled_hook('dap_clear_cache_daily_event');
	}

	// Localization support
	public static function load_domain() {
		// get current language
		$locale = get_locale();
		// locate translation file		
		$mofile = self::$plugin_dir. 'lang/' . self::plugin_domain . '-' . $locale . '.mo';
		$mofile = str_replace(DIRECTORY_SEPARATOR, '/', $mofile);
		// load translation
		load_textdomain(self::plugin_domain, $mofile);
	}

	public static function add_link($strContent) 
	{
		global $post;
		//DO NOT display the button at index , archive ,category page.
		if( !is_singular())
		{
			return $strContent;
		}
		if( ! self::$allow_down_default &&  1 != get_post_meta($post->ID, '_down_as_pdf',TRUE))
		{
			return $strContent;
		}
		$down_as_pdf_options = get_option(self::opt_name);
//    global $wp_query;  //$wp_query->post->ID
		if (in_array($post->post_type, array($down_as_pdf_options['show_in'])) || 'post,page' == $down_as_pdf_options['show_in']) {
			if (!is_feed()) 
			{
				$strHtml = '<div id="downaspdf">
                    <a title="' . __('Download this article as PDF', self::plugin_domain) . '" href="' . WP_PLUGIN_URL .'/down-as-pdf/generate.php?id=' . $post->ID . '" rel="external nofollow">
                      <span>' . stripslashes($down_as_pdf_options['linktext']) . '</span>
                    </a>
                </div>';
			} else {
				$strHtml = sprintf(__('Note: To download this article as PDF, please visit <a href="%s">this post</a> to download the file.', self::plugin_domain), wp_get_shortlink());
			}
			if(strpos($strContent,'[pdf_here]') !== FALSE )
			{
				return str_replace('[pdf_here]',$strHtml,$strContent);
			}
			else
			{
				return $strContent . $strHtml;
			}
		} else {
			return $strContent;
		}
	}

	public static function custom_css() {
		echo '<link rel="stylesheet" type="text/css" media="screen" href="' . WP_PLUGIN_URL . '/down-as-pdf/hacklog-down-as-pdf.css" />' . "\n";
	}

//------------------------ wp admin-------------------------

	public static function settings_menu() {
		add_submenu_page('options-general.php', 'Hacklog Down as PDF', 'Hacklog Down as PDF', 'manage_options', 'Hacklog-Down-as-PDF', array(__CLASS__, 'admin_page'));
	}

	public static function show_message($message) {
		echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
	}

// The admin page
	public static function admin_page() {
		if( isset($_GET['do_act']))
		{
			self::clear_cache_daily();
			self::show_message(__("Cache cleared."));
		}
		// update options in db if requested
		if ($_POST['Submit']) {
			// update linktext
			if (!$_POST['linktext']) {
				$_POST['linktext'] = __('Download as PDF', self::plugin_domain);
			}

			$options['linktext'] = $_POST['linktext'];

			// update download type
			if (!isset($_POST['download_type'])) {
				$_POST['download_type'] = 'D';
			}

			$options['download_type'] = $_POST['download_type'];

			$_POST['font'] = trim($_POST['font']);
			if( in_array($_POST['font'], array_keys(self::get_default_fonts())) )
			{
				$options['font'] = $_POST['font'];
			}
			else
			{
				$options['font'] = self::default_font;
			}
			
			// update download type
			if (!isset($_POST['enable_font_subsetting'])) {
				$_POST['enable_font_subsetting'] = 1;
			}

			$options['enable_font_subsetting'] = $_POST['enable_font_subsetting'];

			// update show_in
			if (!isset($_POST['show_in']) || !in_array($_POST['show_in'], array('post', 'page', 'post,page'))) {
				$_POST['show_in'] = 'post';
			}
			$options['show_in'] = $_POST['show_in'];

			// update font size
			if (!$_POST['main_font_size']) {
				$_POST['main_font_size'] = '10';
			}

			$options['main_font_size'] = $_POST['main_font_size'];


			// update license option 
			if (!isset($_POST['use_cc'])) {
				$_POST['use_cc'] = 1;
			}

			$options['use_cc'] = $_POST['use_cc'];
			$options['cache'] = $_POST['enable_cache'];
			update_option(self::opt_name, $options);

			self::show_message(__("Saved changes."));
		}

		// load options from db to display
		$down_as_pdf_options = get_option(self::opt_name);
		$str_link_text = stripslashes($down_as_pdf_options['linktext']);
		$str_download_type = stripslashes($down_as_pdf_options['download_type']);
		$str_show_in = stripslashes($down_as_pdf_options['show_in']);
		$str_main_font_size = stripslashes($down_as_pdf_options['main_font_size']);
		$str_enable_font_subsetting = stripslashes($down_as_pdf_options['enable_font_subsetting']);
		$str_use_cc = stripslashes($down_as_pdf_options['use_cc']);
		$str_enable_cache = stripslashes($down_as_pdf_options['cache']);
		$current_font = stripslashes($down_as_pdf_options['font']);
		// display options
		?>

		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

			<div class="wrap">
				<h2><?php _e('Hacklog Down as PDF Options', self::plugin_domain); ?></h2>
				<table class="form-table">

					<tr>
						<th scope="row" valign="top">
							<label for="linktext"><?php _e('Link text', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<input type="text" id="linktext" name="linktext" value="<?php echo htmlspecialchars($str_link_text); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="download_type"><?php _e('Download type', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<select name="download_type" id="download_type">
								<option value="I" <?php selected($str_download_type,'I',TRUE); ?> ><?php _e('Show in browser window', self::plugin_domain); ?></option>
								<option value="D" <?php selected($str_download_type,'D',TRUE); ?> ><?php _e('Force download', self::plugin_domain); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="show_in"><?php _e('Place to show the button', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<select name="show_in" id="show_in">
								<option value="post" <?php selected($str_show_in,'post',TRUE); ?> ><?php _e('Post'); ?></option>
								<option value="page" <?php selected($str_show_in,'page',TRUE); ?> ><?php _e('Page'); ?></option>
								<option value="post,page" <?php selected($str_show_in,'post,page',TRUE); ?> ><?php _e('Both Post and Page', self::plugin_domain); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="dap_font"><?php _e('Main Font', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<select name="font" id="dap_font">
								<?php
								$default_fonts = self::get_default_fonts();
								foreach( $default_fonts as $font_type => $font_name )
								{
									$selected = selected($current_font, $font_type,FALSE);
									echo "<option value='{$font_type}' {$selected} >{$font_name}</option>\n";
								}
								?>
							</select>
						</td>
					</tr>
					
					<tr>
						<th scope="row" valign="top">
		<?php _e('Main font size', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="main_font_size">
							<?php 
							for($i=8;$i<=16;++$i)
							{
								echo '<option value=" '. $i . '" '. selected($str_main_font_size, $i , TRUE) .'>'. $i .'</option>';
							}
							?>								
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
		<?php _e('Font Subsetting', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="enable_font_subsetting">
								<option value="1" <?php selected($str_enable_font_subsetting,1,TRUE); ?> > <?php _e('enable', self::plugin_domain); ?></option>
								<option value="0" <?php selected($str_enable_font_subsetting,0,TRUE); ?> > <?php _e('disable', self::plugin_domain); ?></option>
							</select>
							&nbsp; <span class="description"><?php _e('enable font subsetting can reduce the size of the PDF file created but that is very slow and requires a lot of memory.', self::plugin_domain); ?> </span>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
		<?php _e('Enable Cache', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="enable_cache">
								<option value="1" <?php selected( $str_enable_cache, 1, true ); ?> > <?php _e('Yes', self::plugin_domain); ?></option>
								<option value="0" <?php selected( $str_enable_cache, 0, true ); ?> > <?php _e('No', self::plugin_domain); ?></option>
							</select>
							&nbsp; <span class="description"><?php _e('enable disk cache or not.', self::plugin_domain); ?> </span>
						</td>
					</tr>					

					<tr>
						<th scope="row" valign="top">
		<?php _e('Use CC license ?', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="use_cc">
								<option value="1" <?php selected( $str_use_cc, 1, true ); ?> > <?php _e('Yes', self::plugin_domain); ?></option>
								<option value="0" <?php selected( $str_use_cc, 0, true ); ?> > <?php _e('No', self::plugin_domain); ?></option>
							</select>
						</td>
					</tr>


				</table>

				<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Save Changes'); ?>" class="button" />
				</p>

			</div>

		</form>

		<p style="font-size:14px;font-family: Georgia,Monaco,serif,Helvetica;">
		<?php
		echo sprintf(__('Current total cache size: <strong>%s</strong>', self::plugin_domain), self::format_size(self::get_cache_size()) );
		?>
			<a href="<?php echo $_SERVER['REQUEST_URI'];?>&do_act=clear_cache" class="button"><?php _e('Clear cache',self::plugin_domain);?></a>
		</p>
		<p style="font-size:14px;font-family: Georgia,Monaco,serif,Helvetica;">
		<?php
		require_once self::$plugin_dir . 'tcpdf/tcpdf.php';
		$tcpdf = new TCPDF();
		$version = $tcpdf->getTCPDFVersion();
		echo sprintf(__('Current TCPDF Version: <strong>%s</strong>', self::plugin_domain), $version);
		unset($version);
		unset($tcpdf);
		?>
		</p>
		<?php
	}

}

//end class
hacklog_dap::init();