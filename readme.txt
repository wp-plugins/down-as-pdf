=== Hacklog Down As PDF ===
Contributors: ihacklog
Donate link: http://ihacklog.com/donate
Tags: download, pdf,document
Requires at least: 3.2.1
Tested up to: 3.4.1
Stable tag: 2.3.6

This plugin generates PDF documents for visitors when they click the "<strong>Download as PDF</strong>" button below the post. 

== Description ==
This plugin generates PDF documents for visitors when they click the "<strong>Download as PDF</strong>" button below the post. Very useful if you plan to share your posts in PDF format.
Note: You can replace the logo file <strong>logo.png</strong>under <strong>wp-content/plugins/down-as-pdf/images/</strong> with your own.
注意：请将<strong>wp-content/plugins/down-as-pdf/images/</strong>目录下面的<strong>logo.png</strong>文件替换成你自己网站的logo


* 中文介绍请到[插件主页](http://ihacklog.com/?p=3771 "插件主页") 

== Installation ==

1. Upload the whole fold `down-as-pdf` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configiure the plugin via `Settings` ==> `Down As PDF` menu


== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png

  

== Upgrade Notice ==
= 2.3.1 =
* 默认开启下载

= 2.3.0 =
* 默认不开启下载

= 2.2.0 =
* 请将<strong>wp-content/plugins/down-as-pdf/images/</strong>目录下面的<strong>logo.png</strong>文件替换成你自己网站的logo
* Please replace the logo file <strong>logo.png</strong>under <strong>wp-content/plugins/down-as-pdf/images/</strong> with your own.
	


== Changelog ==

= 2.3.6 =
* updated TCPDF lib to version 5.9.172

= 2.3.5 =
* corrected some translation error.

= 2.3.4 =
* use writeHTMLCell other than MultiCell (fixed the bug that can not correctly print copyright info in version 2.3.3)
* add auto make cache dir feature
* upated TCPDF lib to version 5.9.153
* correct the plugin name in readme.txt

= 2.3.3 =
* updated TCPDF lib to version 5.9.152

= 2.3.2 =
* updated TCPDF lib to version 5.9.145

= 2.3.1 =
* changed: set display download button default.if you'd like to control the button display or not in each post,set `private static $allow_down_default = 1;` to `private static $allow_down_default = 0;`

= 2.3.0 =
* changed: added meta value to control if a post is allowed to be downloaded as PDF format.
* fixed: fix a typo in sprintf function on line 81 in previous version.
* added: added shortcode support to control the display posistion of the "Download as PDF" button.

= 2.2.6 =
* added: memory limit and time limit php ini settings

= 2.2.5 =
* added: show backtrace to Administrator

= 2.2.4 =
* fixed: allow Administrator to download private posts as PDF

= 2.2.3 =
* added: password protected posts and not published posts checking
* added: custom font adding tool( upload font file to <strong>wp-content/plugins/down-as-pdf/cache/</strong> and then ,via addf.php?font=the-font-filename.ttf )
* fixed: disabled Disk caching ,for it may takes more than 60s to handle a post.

= 2.2.2 =
* enabled Disk cache by default,added more English language country fonts.
* added "clear cache" button
* TCPDF Lib upgraded to  5.9.142

= 2.2.1 =
* added: Janpanese , Korean ,and Traditional Chinese support.
* added: font selection option
* changed: only show download button in singular pages.
* changed: formated table and block HTML block
* improved: added backtrace action to help to detect the error.

= 2.2.0 =
* fixed: bug when generating PDF for some articles,some words are lost.
* fixed: upgraded the TCPDF lib to  5.9.139 

= 2.1.0 =
* fixed: add more options.

= 1.0.0 =
* released the first version.
