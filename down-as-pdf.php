<?php
/*
  Plugin Name: Down As PDF
  Plugin URI: http://ihacklog.com/?p=3771
  Description: This plugin generates PDF documents for visitors when you click the "<strong>Download as PDF</strong>" button below the post. Very useful if you plan to share your posts in PDF format.You can replace the logo file <strong>logo.png</strong>under <strong>wp-content/plugins/down-as-pdf/images/</strong> with your own.
  Author: <a href="http://www.ihacklog.com" target="_blank" >荒野无灯</a>
  Version: 2.2.0
  Author URI: http://www.ihacklog.com
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

/**
 * @TODO add font select option
 * @todo switch between Text logo and Image logo,and make logo configurable
 * @todo make license option configurable
 */

class hacklog_dap {
	const plugin_domain = 'down-as-pdf';
	private static $plugin_dir = '';

	public static function init() {
		self::$plugin_dir = WP_PLUGIN_DIR . '/down-as-pdf/';
// Hook the admin_menu display to add admin page
		add_action('admin_menu', array(__CLASS__, 'settings_menu'));
		register_activation_hook(__FILE__, array(__CLASS__, 'activate_hook'));
		add_action('dap_clear_cache_daily_event', array(__CLASS__, 'clear_cache_daily'));
		register_deactivation_hook(__FILE__, array(__CLASS__, 'deactivate_hook'));
		add_action('init', array(__CLASS__, 'load_domain'));
//link in post to generate PDF
		add_filter('the_content', array(__CLASS__, 'add_link'));
		add_action('wp_head', array(__CLASS__, 'custom_css'));
		add_action('admin_notices', array(__CLASS__, 'admin_notice'));
	}
	
	public static function get_plugin_dir()
	{
		return self::$plugin_dir;
	}

	public static function admin_notice() {
		if (substr($_SERVER["PHP_SELF"], -11) == 'plugins.php' && function_exists("admin_url") && !self::is_writeable_ACLSafe(self::$plugin_dir . 'cache')) {
			echo '<div class="error"><p><strong>' . sprintf(__("Error:%s is not writable!", self::$plugin_dir . 'cache', self::plugin_domain)) . '</strong></p></div>';
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
			'use_cc' => 1,
		);
		add_option('down_as_pdf_options', $default_options);
		//scheduly clear cache file
		wp_schedule_event(time(), 'daily', 'dap_clear_cache_daily_event');
	}

	public static function clear_cache_daily() {
		$cache_dir = WP_PLUGIN_DIR . '/down-as-pdf/cache';
		// do something every day
		if (is_dir($cache_dir)) {
			if ($handle = @opendir($cache_dir)) {
				while (false !== ( $file = readdir($handle))) {
					if ('.' != $file && '..' != $file && !is_dir($file)) {
						@unlink($file);
					}
				}
				closedir($handle);
			}
		}
	}

	public static function deactivate_hook() {
		delete_option('down_as_pdf_options');
		wp_clear_scheduled_hook('dap_clear_cache_daily_event');
	}

	// Localization support
	public static function load_domain() {
		// get current language
		$locale = get_locale();
		// locate translation file		
		$mofile = WP_PLUGIN_DIR . '/' . basename(dirname(__FILE__)) . '/lang/' . self::plugin_domain . '-' . $locale . '.mo';
		$mofile = str_replace(DIRECTORY_SEPARATOR, '/', $mofile);
		// load translation
		load_textdomain(self::plugin_domain, $mofile);
	}

	public static function add_link($strContent) {
		global $post;
		$down_as_pdf_options = get_option('down_as_pdf_options');
//    global $wp_query;  //$wp_query->post->ID
		if (in_array($post->post_type, array($down_as_pdf_options['show_in'])) || 'post,page' == $down_as_pdf_options['show_in']) {
			if (!is_feed()) {
				$strHtml = '<div id="downaspdf">
                    <a title="' . __('Download this article as PDF', self::plugin_domain) . '" href="' . get_bloginfo('wpurl') . '/wp-content/plugins/down-as-pdf/generate.php?id=' . $post->ID . '">
                      <span>' . stripslashes($down_as_pdf_options['linktext']) . '</span>
                    </a>
                </div>';
			} else {
				$strHtml = sprintf(__('Note: To download this article as PDF, please visit <a href="%s">this post</a> to download the file.', self::plugin_domain), wp_get_shortlink());
			}

			return $strContent . $strHtml;
		} else {
			return $strContent;
		}
	}

	public static function custom_css() {
		echo '<link rel="stylesheet" type="text/css" media="screen" href="' . site_url('/wp-content/plugins/down-as-pdf/down-as-pdf.css" />') . "\n";
	}

//------------------------ wp admin-------------------------

	public static function settings_menu() {
		add_submenu_page('options-general.php', 'Down as PDF', 'Down as PDF', 'manage_options', 'Down-as-PDF', array(__CLASS__, 'admin_page'));
	}

	public static function show_message($message) {
		echo "<div id=\"message\" class=\"updated fade\"><p>$message</p></div>\n";
	}

// The admin page
	public static function admin_page() {
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
			update_option('down_as_pdf_options', $options);

			self::show_message(__("Saved changes."));
		}

		// load options from db to display
		$down_as_pdf_options = get_option('down_as_pdf_options');
		$strLinkText = stripslashes($down_as_pdf_options['linktext']);
		$strDownloadType = stripslashes($down_as_pdf_options['download_type']);
		$strShowIn = stripslashes($down_as_pdf_options['show_in']);
		$strMainFontSize = stripslashes($down_as_pdf_options['main_font_size']);
		$strEnableFontSubsetting = stripslashes($down_as_pdf_options['enable_font_subsetting']);
		$str_use_cc = stripslashes($down_as_pdf_options['use_cc']);
		// display options
		?>

		<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">

			<div class="wrap">
				<h2><?php _e('Down as PDF Options', self::plugin_domain); ?></h2>
				<table class="form-table">

					<tr>
						<th scope="row" valign="top">
							<label for="linktext"><?php _e('Link text', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<input type="text" id="linktext" name="linktext" value="<?php echo htmlspecialchars($strLinkText); ?>" />
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="download_type"><?php _e('Download type', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<select name="download_type" id="download_type">
								<option value="I" <?php if ($strDownloadType == 'I')
			echo 'selected="selected"'; ?> ><?php _e('Show in browser window', self::plugin_domain); ?></option>
								<option value="D" <?php if ($strDownloadType == 'D')
			echo 'selected="selected"'; ?> ><?php _e('Force download', self::plugin_domain); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
							<label for="show_in"><?php _e('Place to show the button', self::plugin_domain); ?>:</label>
						</th>
						<td>
							<select name="show_in" id="show_in">
								<option value="post" <?php if ($strShowIn == 'post')
			echo 'selected="selected"'; ?> ><?php _e('Post'); ?></option>
								<option value="page" <?php if ($strShowIn == 'page')
			echo 'selected="selected"'; ?> ><?php _e('Page'); ?></option>
								<option value="post,page" <?php if ($strShowIn == 'post,page')
			echo 'selected="selected"'; ?> ><?php _e('Both Post and Page', self::plugin_domain); ?></option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
		<?php _e('Main font size', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="main_font_size">
								<option value="8" <?php if ($strMainFontSize == '8')
			echo 'selected="selected"'; ?> >8</option>
								<option value="9" <?php if ($strMainFontSize == '9')
			echo 'selected="selected"'; ?> >9</option>
								<option value="10" <?php if ($strMainFontSize == '10')
			echo 'selected="selected"'; ?> >10</option>
								<option value="11" <?php if ($strMainFontSize == '11')
			echo 'selected="selected"'; ?> >11</option>
								<option value="12" <?php if ($strMainFontSize == '12')
			echo 'selected="selected"'; ?> >12</option>
								<option value="13" <?php if ($strMainFontSize == '13')
			echo 'selected="selected"'; ?> >13</option>
								<option value="14" <?php if ($strMainFontSize == '14')
			echo 'selected="selected"'; ?> >14</option>
							</select>
						</td>
					</tr>

					<tr>
						<th scope="row" valign="top">
		<?php _e('Font Subsetting', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="enable_font_subsetting">
								<option value="1" <?php if ($strEnableFontSubsetting == '1')
			echo 'selected="selected"'; ?> > <?php _e('enable', self::plugin_domain); ?></option>
								<option value="0" <?php if ($strEnableFontSubsetting == '0')
			echo 'selected="selected"'; ?> > <?php _e('disable', self::plugin_domain); ?></option>
							</select>
							&nbsp; <span class="description"><?php _e('enable font subsetting can reduce the size of the PDF file created but that is very slow and requires a lot of memory.', self::plugin_domain); ?> </span>
						</td>
					</tr>


					<tr>
						<th scope="row" valign="top">
		<?php _e('Use CC license ?', self::plugin_domain); ?>:
						</th>
						<td>
							<select name="use_cc">
								<option value="1" <?php if ($str_use_cc == 1)
			echo 'selected="selected"'; ?> > <?php _e('Yes', self::plugin_domain); ?></option>
								<option value="0" <?php if ($str_use_cc == 0)
			echo 'selected="selected"'; ?> > <?php _e('No', self::plugin_domain); ?></option>
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