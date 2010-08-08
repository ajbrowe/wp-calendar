<?php
/*
Plugin Name: WP Calendar
Plugin URI: http://www.faebusoft.ch/downloads/wp-calendar
Description: WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage.
Author: Fabian von Allmen
Author URI: http://www.faebusoft.ch
Version: 1.1.5
License: GPL
Last Update: 08.08.2010
*/

define('FSE_DATE_MODE_ALL', 1); // Event is valid in the interval
define('FSE_DATE_MODE_START', 2); // Event starts in the interval
define('FSE_DATE_MODE_END', 3); // Event ends in the interval

define('FSE_GROUPBY_NONE', ''); // Event grouping by day
define('FSE_GROUPBY_DAY', 'd'); // Event grouping by day
define('FSE_GROUPBY_MONTH', 'm'); // Event grouping by month
define('FSE_GROUPBY_YEAR', 'y'); // Event grouping by year

require_once('fsCalendarSettings.php');
require_once('fsCalendarAdmin.php');
require_once('fsCalendarEvent.php');
require_once('fsCalendarWidgets.php');
require_once('fsCalendarFunctions.php');

class fsCalendar {
	
	static $plugin_name     = 'Calendar';
	static $plugin_vers     = '1.1.2';
	static $plugin_id       = 'fsCal'; // Unique ID
	static $plugin_options  = '';
	static $plugin_filename = '';
	static $plugin_dir      = '';
	static $plugin_url      = '';
	static $plugin_css_url  = '';
	static $plugin_img_url  = '';
	static $plugin_js_url   = '';
	static $plugin_lang_dir = '';
	static $plugin_textdom  = '';
	
	static $valid_states;
	
	
	static $valid_fields = array('eventid', 
						 		 'subject', 
								 'tsfrom', 
								 'tsto', 
								 'allday', 
								 'description', 
								 'location', 
								 'categories',
								 'author', 
 								 'createdate',
								 'publishauthor',
								 'publishdate',
								 'state'
								 );

	var $admin;
								 
	// Options for Fullcalendar
	/*static $full_calendar_header_opts = array('title', 'prev', 'next', 'prevYear', 
											  'nextYear', 'today');
	static $full_calendar_view_opts = array('month', 'basicWeek','basicDay', 'agendaWeek', 'agendaDay');
	static $full_calendar_weekmode_opts = array('fixed', 'liquid', 'variable');*/

	function fsCalendar() {
		global $wpdb;
		
		// Init Vars
		self::$plugin_options  = array(
									'fse_df_wp' => 1,
									'fse_df'    => 'd.m.Y',
									'fse_tf_wp' => 1,
									'fse_tf'    => 'H:i',
									'fse_ws_wp' => 1,
									'fse_ws'    => 1,
									'fse_df_admin' => 'dmY',
									'fse_df_admin_sep' => '.',
									'fse_page' => '',
									'fse_page_mark' => true,
									'fse_page_hide' => true,
									'fse_number' => 10,
									'fse_template' => '<p><strong><a href="{event_url}" title="{event_subject}">{event_subject}</a></strong><br />{event_startdate} {event_starttime} - {event_enddate} {event_endtime} @ {event_location}</p>',
									'fse_template_lst' => '<stront><a href="{event_url}">{event_subject}</a></strong><br />{event_startdate} {event_starttime} - {event_enddate} {event_endtime} @ {event_location}',
									'fse_show_enddate' => 0,
									'fse_groupby' => 'm',
									'fse_groupby_header' => 'M Y',
									'fse_page_create_notice' => 0,
									'fse_adm_gc_enabled' => 1,
									'fse_adm_gc_mode' => 0,
									'fse_adm_gc_show_week' => 0,
									'fse_adm_gc_show_sel' => 1,
									'fse_fc_tit_week_fmt'=>"F d[ Y]{ '&#8212;'[ F] d Y}",
									'fse_fc_tit_month_fmt'=>'F Y',
									'fse_fc_tit_day_fmt'=>'l, F j, Y',
									'fse_fc_col_week_fmt'=>'D m/j',
									'fse_fc_col_month_fmt'=>'l',
									'fse_fc_col_day_fmt'=>'l m/j',
									'fse_load_jquery'=>1,
									'fse_load_jqueryui'=>1,
									'fse_allday_hide_time'=>1
								);
		self::$plugin_filename = plugin_basename( __FILE__ );
		self::$plugin_dir      = dirname(self::$plugin_filename);
		self::$plugin_url      = trailingslashit(WP_PLUGIN_URL).self::$plugin_dir.'/';
		self::$plugin_css_url  = self::$plugin_url.'css/';
		self::$plugin_img_url  = self::$plugin_url.'images/';
		self::$plugin_js_url   = self::$plugin_url.'js/';
		self::$plugin_lang_dir = trailingslashit(self::$plugin_dir).'lang/';
		self::$plugin_textdom  = 'fsCalendar';
		
		
		// General/Frontend Hooks
		add_action('init',                 array(&$this, 'hookInit'));
		add_action('init',                 array(&$this, 'hookRegisterScripts'));
		add_action('init',                 array(&$this, 'hookRegisterStyles'));
		add_action('widgets_init', 		   array(&$this, 'hookRegisterWidgets'));
		
		add_action( 'wp_ajax_nopriv_wpcal-getevents', 
										   array(&$this, 'hookAjaxGetEvents'));
		add_action( 'wp_ajax_wpcal-getevents', 
										   array(&$this, 'hookAjaxGetEvents'));
		
		add_filter('the_title',            array(&$this, 'hookFilterTitle'), 1, 2);
		add_filter('wp_title',             array(&$this, 'hookFilterPageTitle'));
		add_filter('the_content',          array(&$this, 'hookFilterContent'));
		add_filter('get_pages',            array(&$this, 'hookHidePageFromSelection'));
		
		register_activation_hook(__FILE__, array(&$this, 'hookActivate'));
		register_uninstall_hook(__FILE__,  array(&$this, 'hookUninstall'));
		
		// Init Admin
		if (is_admin()) {
			$this->admin = new fsCalendarAdmin();
		}
	}

	/**
	 * Initialize some vars
	 * @return void
	 */
	function hookInit() {
		load_plugin_textdomain(self::$plugin_textdom, false, self::$plugin_lang_dir);
		self::$valid_states    = array('draft'=>__('Draft', self::$plugin_textdom),
									   'publish'=>__('Published', self::$plugin_textdom));
	}
	
	/**
	 * Register Styles to Load
	 * @return void
	 */
	function hookRegisterStyles() {
		if (!is_admin()) {
			// Check if user has its own CSS file in the theme folder
			$custcss = get_template_directory().'/fullcalendar.css';
			if (file_exists($custcss))
				$css = get_bloginfo('template_url').'/fullcalendar.css';
			else
				$css = self::$plugin_css_url.'fullcalendar.css';
			wp_enqueue_style('fullcalendar', $css);
		}
	}
	
	/**
	 * Register Scripts to Load
	 * @return void
	 */
	function hookRegisterScripts() {
		if (!is_admin()) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('fullcalendar', self::$plugin_js_url.'fullcalendar.js');
			//Pass Ajax Url to Javascript Paraemter
			wp_localize_script('fullcalendar', 'WPCalendar', array('ajaxUrl'=>admin_url('admin-ajax.php')));
		}
	}
	
	
	/**
	 * Register Widgets
	 * @return void
	 */
	function hookRegisterWidgets() {
		register_widget('WPCalendarGrouped');
		register_widget('WPCalendarSimple');
	}
		
	/**
	 * Replaces any {event*} tags in the title
	 * @param $title
	 * @param $postid
	 * @return String post title
	 */
	function hookFilterTitle($title, $postid = -1) {
		// Make sure, that the titles are not filtered in admin interface
		$req = $_SERVER['REQUEST_URI'];
		
		
		if (strpos($req, 'edit-pages.php') === false &&
		    strpos($req, 'edit.php?post_type=page') === false) {
			return $this->hookFilterContent($title);
		} else {
			// Get Page Id from settings and mark it
			$pageid = intval(get_option('fse_page'));
			$pagemark = intval(get_option('fse_page_mark'));
			if (!empty($pageid) && $pageid == $postid && $pagemark == 1) {
				return '<span id="page_is_cal"><span>'.$title.'</span></span>';
			} else {
				return $title;
			}
		}
	}
	
	/**
	 * Replaces and {event*} tag in the page title
	 * @param $title
	 * @return Filtered page title
	 */
	function hookFilterPageTitle($title) {
		return $this->hookFilterContent($title);
	}
	
	/**
	 * Replaces and {event*} tag in the content
	 * @param $content
	 * @return unknown_type
	 */
	function hookFilterContent($content) {
		return $this->filterContent($content);
	}
	
	/**
	 * Hides single page view from being selected
	 * @param $pages Array of pages
	 * @return Array of pages
	 */
	function hookHidePageFromSelection($pages) {
		
		// Never hide in admin interface
		if (is_admin()) {
			return $pages;
		}
		
		$pagehide = intval(get_option('fse_page_hide'));
		$pageid = intval(get_option('fse_page'));
		
		if ($pagehide != 1 || empty($pageid)) {
			return $pages;
		}
		
		foreach($pages as $k => $p) {
			if ($p->ID == $pageid) {
				unset($pages[$k]);
				break;
			}
		}
		
		return $pages;
	}
	
	/**
	 * Ajax hook 
	 * nice tutor: http://www.wphardcore.com/2010/5-tips-for-using-ajax-in-wordpress/
	 */
	function hookAjaxGetEvents() {
		$start = intval($_POST['start']);
		$end   = intval($_POST['end']);
		
		$args['datefrom'] = $start;
		$args['dateto']   = $end;
		$args['datemode'] = FSE_DATE_MODE_ALL;
		$args['number']   = 0;
		
		if (isset($_POST['state']))
			$args['state'] = $_POST['state'];
		if (isset($_POST['author']))
			$args['author'] = $_POST['author'];
		if (isset($_POST['categories']))
			$args['categories'] = $_POST['categories'];
		if (isset($_POST['include']))
			$args['include'] = $_POST['include'];
		if (isset($_POST['exclude']))
			$args['exclude'] = $_POST['exclude'];
		$events = $this->getEventsExternal($args);
		
		// Process array of events
		$events_out = array();
		foreach($events as $evt) {
			unset($e);
			$e['id'] = $evt->eventid;
			$e['title'] = $evt->subject;
			$e['allDay'] = ($evt->allday == 1 ? true : false);
			$e['start'] = date('c', $evt->tsfrom);
			$e['end'] = date('c', $evt->tsto);
			$e['editable'] = false;
			
			$classes = array();
			foreach($evt->categories as $c) {
				$classes[] = 'category-'.$c;
			}
			if (count($classes) > 0) {
				$e['className'] = $classes;
			}
			
			$events_out[] = $e;
		}
		
		$response = json_encode($events_out);
		
		header("Content-Type: application/json");
		echo $response;
		
		exit;
	}
	
	/**
	 * Filters all {event*} tags
	 * @param $content Content to filter
	 * @param $evt Event Object (optional)
	 * @return String Filtered content
	 */
	function filterContent($content, $evt = NULL) {
				
		// Match all tags, but make sure that no escaped {} are selected!
		preg_match_all('/[^\\\]?(\{event[s]?_(.+?[^\\\])\})/is', $content, $matches, PREG_SET_ORDER);
		
		foreach($matches as $k => $m) {
			$matches[$k][0] = $m[1];
			$matches[$k][1] = $m[2];
			unset($matches[$k][2]);
		}
		
		if (count($matches) == 0) {
			return $content;
		}
		
		// Get Page url if any
		$page_id = intval(get_option('fse_page'));
		if (!empty($page_id)) {
			$page_url = get_permalink($page_id);
			if (!empty($page_url)) {
				if (strpos($page_url, '?') === false)
					$page_url .= '?event=';
				else
					$page_url .= '&event=';	
			}
		}

		$showenddate = get_option('fse_show_enddate') == 1 ? true : false;
		$hideifallday = get_option('fse_allday_hide_time') == 1 ? true : false;
		
		if (!empty($evt)) {
		// We just create an event object, if it does no exist, all var are empty!
			
		} elseif (isset($_GET['event'])) {
			$evt = new fsEvent(intval($_GET['event']), 'publish');
		} else {
			// Load an empty event, to get all attributes in the correct format
			$evt = new fsEvent(-1);	
		}
		
		// Calculate duration
		$start = floor($evt->tsfrom/60);
		$end   = floor($evt->tsto/60);
		$diff  = $end - $start;
		
		
		if ($evt->allday == 1) {
			$dur_days = floor($diff / 1440)+1; // Add 1 day
		} else {
			$dur_days = floor($diff / 1440);
			$diff -= ($dur_days * 1440);
			$dur_hours = floor($diff / 60);
			$diff -= ($dur_hours * 60);
			$dur_minutes = $diff;
		}

		foreach($matches as $m) {
			//$token = explode(';', $m[1]);
			$token = array();
			$qopen = false;
			$esc   = false;
			$temp  = '';
			
			// Covert URL Encodings
			$m[1] = html_entity_decode($m[1]);
			$m[1] = str_replace(array('&#8221;', '&#8243;', '&#8220;'), array('"', '"', '"'), $m[1]);
			/*$m[1] = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $m[1]);
			$m[1] = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $m[1]);*/
						
			for ($i=0; $i<strlen($m[1]); $i++) {
				if ($m[1][$i] == '"' && $esc == false) {
					$qopen = !$qopen;
					$esc   = false;
					$temp .= '"';
				} elseif ($m[1][$i] == "\\") {
					// Maybe already escaped, just add it as well
					if ($esc == true) {
						$temp .= '\\'; // Make 2!
						$esc = false;	
					} else {
						$esc = true;
					}
				} elseif ($m[1][$i] == ';' && $qopen == false) {
					$token[] = trim($temp);
					$temp = '';	
					$esc = false;
				} else {
					$temp .= $m[1][$i];
					$esc = false;
				}
			}
			if (!empty($temp)) {
				$token[] = $temp;	
			}
			
			$opts = array();
			$opts_orig = array();
			if (count($token) > 1) {
				for($i=1; $i<count($token); $i++) {
					list($opt_orig, $val) = explode('=', $token[$i]);
					
					$val = trim($val);
					$opt_orig = trim($opt_orig);
					$opt = strtolower($opt_orig);
					
					// Remove " "
					preg_match('/^"(.*)"$/s', $val, $matches);
					if (count($matches) > 0) {
						$val = $matches[1];
					}
					
					$opts[$opt] = $val;
					$opts_orig[$opt_orig] = $val;
				}
			}
			$tag = strtolower(trim($token[0]));

			// Reset!
			$rep = '';
			
			switch($tag) {
				case 'id':
					if (isset($opts['id'])) {
						unset($evt);
						$evt = new fsEvent(intval($opts['id']), 'publish');
						$rep = '';
					} else {
						$rep = $evt->eventid;	
					}
					break;
				case 'subject':
					if (empty($evt->subject)) {
						$rep = __('Event not found');	
					} else {
						$rep = $evt->subject;
					}
					break;
				case 'location':
					$rep = $evt->location;
					break;
				case 'description':
					$rep = $evt->getDescription();
					break;
				case 'author':
					$rep = $evt->author_t;
					break;
				case 'publisher':
					$rep = $evt->publishauthor_t;
					break;
				case 'authorid';
					$rep = $evt->author;
					break;
				case 'publisherid':
					$rep = $evt->publishauthor;
					break; 
				case 'startdate':
					if (!empty($evt->tsfrom)) {
						if (isset($opts['fmt']))
							$rep = $evt->getStart($opts['fmt'], 2);
						else
							$rep = $evt->getStart('', 2);
					} else {
						$rep = '';
					}
					break;
				case 'enddate':
					if (!empty($evt->tsto)) {
						if (isset($opts['alwaysshowenddate']))
							$l_sed = ($opts['alwaysshowenddate'] == 1 ? true : false);
						else
							$l_sed = $showenddate;
						
						// Do not display if date AND time is the same
						if ($l_sed == false && 
							( date('d', $evt->tsto) == date('d', $evt->tsfrom) &&
							  date('m', $evt->tsto) == date('m', $evt->tsfrom) &&
							  date('Y', $evt->tsto) == date('Y', $evt->tsfrom) )) {
							$rep = '';
						} else {
							if (isset($opts['fmt']))
								$rep = $evt->getEnd($opts['fmt'], 2);
							else
								$rep = $evt->getEnd('', 2);
						}
					} else {
						$rep = '';	
					}
					break;
				case 'starttime':
					if (!empty($evt->tsfrom)) {
						if (isset($opts['hideifallday']))
							$l_hide = ($opts['hideifallday'] == 1 ? true : false);
						else
							$l_hide = $hideifallday;
						
						if ($evt->allday == true && $l_hide == true) {
							$rep = '';
						} else {
							// Do not display if date AND time is the same
							if (isset($opts['fmt']))
								$rep = $evt->getStart($opts['fmt'], 3);
							else
								$rep = $evt->getStart('', 3);
						}
					} else {
						$rep = '';	
					}
					break;
				case 'endtime':
					if (!empty($evt->tsto)) {
						if (isset($opts['hideifallday']))
							$l_hide = ($opts['hideifallday'] == 1 ? true : false);
						else
							$l_hide = $hideifallday;
						
						// Do not display if date AND time is the same
						if (isset($opts['alwaysshowenddate']))
							$l_sed = ($opts['alwaysshowenddate'] == 1 ? true : false);
						else
							$l_sed = $showenddate;
							
						if (($evt->allday == true && $l_hide == true) || 
							($l_sed == false && $evt->tsfrom == $evt->tsto)) {
							$rep = '';
						} else {
							if (isset($opts['fmt']))
								$rep = $evt->getEnd($opts['fmt'], 3);
							else
								$rep = $evt->getEnd('', 3);
						}
					} else {
						$rep = '';	
					}
					break;
				case 'duration':
					$t = $opts['type'];
					$a = $opts['suffix'];
					$e = (isset($opts['empty']) ? $opts['empty'] : 0);
					if (in_array($t, array('d','h','m'))) {
						if ($evt->allday == 1) {
							if ($t == 'd')
								$rep = $dur_days.$a; // Always one, so no empty check
							else
								$rep = '';
						} else {
							switch($t) {
								case 'd':
									$rep = $dur_days;
									break;
								case 'h':
									$rep = $dur_hours;
									break;
								case 'm':
									$rep = $dur_minutes;
									break;	
							}
							if (empty($rep) && $e != 1)
								$rep = '';
							else 
								$rep .= $a;
						}
					} else {
						$rep = '';	
					}
					break;
				case 'allday':
					if ($evt->allday == true && isset($opts['text'])) {
						$rep = $opts['text'];
					} else {
						$rep = '';
					}
					break;
				case 'publishdate':
					if (!empty($evt->publishdate)) {
						if (isset($opts['fmt']))
							$rep = date_i18n($opts['fmt'], $evt->publishdate);
						else
							$rep = date_i18n('d.m.Y', $evt->publishdate);
					} else {
						$rep = '';	
					}
					break;
				case 'publishtime':
					if (!empty($evt->publishdate)) {
						if (isset($opts['fmt']))
							$rep = date_i18n($opts['fmt'], $evt->publishdate);
						else
							$rep = date_i18n('H:i', $evt->publishdate);
					} else {
						$rep = '';	
					}
					break;
				case 'categories':
					$excl = array();
					if (isset($opts['exclude'])) {
						$excl = explode(',', $opts['exclude']);	
					}
					if (isset($opts['sep'])) {
						$sep = $opts['sep'];	
					} else {
						$sep = ', ';	
					}
					
					$rep = '';
					$first = true;
					foreach($evt->categories_t as $k => $c) {
						if (!in_array($k, $excl)) {
							if ($first == true) {
								$first = false;
							} else {
								if ($sep != 'list') {
									$rep .= $sep;
								}
							}
							if ($sep == 'list') {
								$rep .= '<li>'.$c.'</li>';
							} else {
								$rep .= $c;	
							}
						}
					}
					
					if ($sep == 'list') {
						$rep = '<ul class="eventcategories">'.$rep.'</ul>';	
					}
					break;
				case 'url':
					if (!empty($page_url) && !empty($evt->eventid))
						$rep = $page_url.$evt->eventid;
					else
						$rep = '';
					break;
				case 'print':
					$opts['echo'] = false; // No echo!
					$rep = $this->printEvents($opts);
					break;
				case 'printlist':
					$opts['echo'] = false; // No echo!
					$rep = $this->printEventsList($opts);
					break;
				case 'calendar':
					$uniqueId = substr(uniqid('fscal-'), 0, 12);
					$rep = '<div id="'.$uniqueId.'"></div>';
					$rep .= "<script type=\"text/javascript\">jQuery(document).ready(function() {jQuery('#$uniqueId').fullCalendar({";
					
					
					// Convert hierarchical options
					if (is_array($opts_orig)) {
						foreach($opts_orig as $key => $val) {
							$keys = explode('->', $key);
							// Process from the last to the second
							for ($i=count($keys)-1; $i>0; $i--) {
								$tmp[trim($keys[$i])] = $val;
								$val = $tmp;
							}
							
							if (trim($keys[0]) != $key) {
								unset($opts_orig[$key]);
							}
							
							$opts_orig[trim($keys[0])] = $val;
						}
					}
					
					// First day of week
					if (!isset($opts_orig['firstDay'])) {
						if (get_option('fse_ws_wp') == 1) {
							$weekstart = get_option('start_of_week');
						} else {
							$weekstart = get_option('fse_ws');	
						}
						$rep .= "firstDay: $weekstart,";
					}
					
					// Date formats
					if (!isset($opts_orig['timeFormat'])) {
						$fmt = $this->convertDateFmt(get_option('time_format'));
						$rep .= "timeFormat: \"$fmt\",";
					}
					
					// Translation of month and day names
					if (!isset($opts_orig['monthNames'])) {
						$rep .= 'monthNames: ["'.implode('","', $GLOBALS['month']).'"],';
					}
					
					if (!isset($opts_orig['monthNamesShort'])) {
						$rep .= 'monthNamesShort: ["'.implode('","', $GLOBALS['month_abbrev']).'"],';
					}
					
					if (!isset($opts_orig['dayNames'])) {
						$rep .= 'dayNames: ["'.implode('","', $GLOBALS['weekday']).'"],';
					}
					
					if (!isset($opts_orig['dayNamesShort'])) {
						$rep .= 'dayNamesShort: ["'.implode('","', $GLOBALS['weekday_abbrev']).'"],';
					}
					
					if (!isset($opts_orig['titleFormat'])) {
						$rep .= 'titleFormat: {'.
								'month: "'.addslashes($this->convertDateFmt(get_option('fse_fc_tit_month_fmt'))).'",'.
								'week: "'.addslashes($this->convertDateFmt(get_option('fse_fc_tit_week_fmt'))).'",'.
								'day: "'.addslashes($this->convertDateFmt(get_option('fse_fc_tit_day_fmt'))).'"'.
								'},';
					}
					if (!isset($opts_orig['columnFormat'])) {
						$rep .= 'columnFormat: {'.
								"month: '".addslashes($this->convertDateFmt(get_option('fse_fc_col_month_fmt')))."',".
								"week: '".addslashes($this->convertDateFmt(get_option('fse_fc_col_week_fmt')))."',".
								"day: '".addslashes($this->convertDateFmt(get_option('fse_fc_col_day_fmt')))."'".
								'},';
					}
					
					// Button Texts
					if (!isset($opts_orig['buttonText'])) {
						$rep .= 'buttonText: {'.
							"prev: '".__('&nbsp;&#9668;&nbsp;', self::$plugin_textdom)."',".
						 	"next: '".__('&nbsp;&#9658;&nbsp;', self::$plugin_textdom)."',".
							"prevYear: '".__('&nbsp;&lt;&lt;&nbsp;', self::$plugin_textdom)."',".
							"nextYear: '".__('&nbsp;&gt;&gt;&nbsp;', self::$plugin_textdom)."',".
							"today: '".__('today', self::$plugin_textdom)."',".
							"month: '".__('month', self::$plugin_textdom)."',".
							"week: '".__('week', self::$plugin_textdom)."',".
							"day: '".__('day', self::$plugin_textdom)."'},";
					}
										
					//Add all original options
					if (is_array($opts_orig)) {
						foreach($opts_orig as $key => $val) {						
							$rep .= $this->filterContentProcessCalOpts($key, $val);
						}
					}

					// Link Click
					if (isset($page_url)) {
						$rep .= "eventClick: function(calEvent, jsEvent, view) {document.location.href='$page_url'+calEvent.id;},";
					}
					
					$rep .= "events: function(start, end, callback) {
					    	jQuery.post(
					    		WPCalendar.ajaxUrl,
					    		{
					    			action: 'wpcal-getevents',
					                start: Math.round(start.getTime() / 1000),
					                end: Math.round(end.getTime() / 1000)".
					                (isset($opts['include']) ? ",include:'".$opts['include']."'" : '').
					                (isset($opts['exclude']) ? ",exclude:'".$opts['exclude']."'" : '').
					                (isset($opts['categories']) ? ",categories:'".$opts['categories']."'" : '').
					                (isset($opts['state']) ? ",state:'".$opts['state']."'" : '').
					                (isset($opts['author']) ? ",author:'".$opts['author']."'" : '').
					    		"},
					    		function(events) {
					    			var evt = eval(events);
					    			callback(evt);
					    		}
					    	);
					    },";
					
					if ($rep[strlen($rep)-1] == ',') {
						$rep = substr($rep, 0, strlen($rep)-1);	
					}
					
					$rep .= '})});';
					
					$rep .= '</script>';
					break;
			}
			$content = preg_replace('/'.preg_quote($m[0], '/').'/', str_replace('$','\$',$rep), $content, 1);
		}
		
		return $content;
	}
	
	function filterContentProcessCalOpts($key, $val) {
		$ret = $key.': ';
		if (!is_array($val)) {
			$ret .= (is_numeric($val) ? '' : '"').$val.(is_numeric($val) ? '' : '"');
		} else {
			$ret .= '{';
			
			foreach($val as $k => $v) {
				$ret .= $this->filterContentProcessCalOpts($k, $v);
			}
			
			// Remove comma at the end
			$ret = substr($ret, 0, strlen($ret)-1);
			
			$ret .= '}';
		}
		$ret .= ',';
		return $ret;
	}
	
	/**
	 * Prints or returns a list of events (for external use)
	 * @param $args @see getEventsExternal
	 * @return unknown_type
	 */
	function printEvents($args = array()) {
		$echo = true;
		$before = $after = '';
		
		$template = get_option('fse_template');
		$showend  = get_option('fse_show_enddate');
		
		foreach($args as $k => $a) {
			switch($k) {
				case 'echo':
					if (is_bool($a))
						$echo = $a;
					break;
				case 'before':
					$before = $a;
					break;
				case 'after':
					$after = $a;
					break;
				case 'template':
					$template = $a;
					break;
				case 'alwaysshowenddate':
					if (is_bool($a))
						$showend = $a;
					break;
			}
		}
		
		$ret = '';
		$evt = $this->getEventsExternal($args);
		
		foreach($evt as $e) {
			$ret .= $this->filterContent($template, $e);
		}
		
		$ret = $before.$ret.$after;
		
		if ($echo == true)
			echo $ret;
		else
			return $ret;
	}
	
	/**
	 * Prints or returns an unordered list (external use)
	 * @param $args @see getEventsExternal
	 * @return unknown_type
	 */
	function printEventsList($args = array()) {
		$echo = true;
		$before = $after = '';
		
		$template = get_option('fse_template_lst');
		$groupby = get_option('fse_groupby');
		$groupby_hdr = get_option('fse_groupby_header');
		
		foreach($args as $k => $a) {
			switch($k) {
				case 'echo':
					if (is_bool($a))
						$echo = $a;
					break;
				case 'before':
					$before = $a;
					break;
				case 'after':
					$after = $a;
					break;
				case 'template':
					$template = $a;
					break;
				case 'groupby':
					if (in_array($a, array('d','m','y')))
						$groupby = $a;
					break;
				case 'groupby_header':
					$groupby_hdr = $a;
					break;
			}
		}
		
		// Sort must be by date, the user can choos, if asc or desc...
		if (isset($filter['orderby'])) {
			unset($filter['orderby']);
		}
		$filter['orderby'] = array('tsfrom');
		
		if (isset($filter['orderdir'])) {
			$dir = $filter['orderdir'];
			if (isset($dir[0]))
				$dir = $dir[0];
			else
				$dir = 'asc';
			unset($filter['orderdir']);
		} else {
			$dir = 'asc';	
		}
		$filter['orderdir'] = array($dir);
		
		$ret = '';
		$evt = $this->getEventsExternal($args);
		
		if ($evt !== false) {
			$d = $m = $y = -1;
			foreach($evt as $e) {
				$dn = $e->getStart('d');
				$mn = $e->getStart('m');
				$yn = $e->getStart('y');
				
				if (($groupby == 'y' && $yn != $y) ||
				    ($groupby == 'm' && ($yn != $y || $mn != $m)) ||
				    ($groupby == 'd' && ($yn != $y || $mn != $m || $dn != $d))) {
				
				    //echo $yn.'-'.$y.':'.$mn.'-'.$m.'<br />';
				    	
					if ($d != -1) {
						$ret .= '</ul></li>';
					}
					$ret .= '<li class="event_header">'.$e->getStart($groupby_hdr).'<ul class="events">';
					$d = $dn;
					$m = $mn;
					$y = $yn;
				}
				$ret .= '<li class="event" id="event-'.$e->eventid.'">';
				$ret .= $this->filterContent($template, $e);
				$ret .= '</li>';
			}
			if ($d != -1) {
				$ret .= '</ul></li>';
			}
		}
		
		$ret = $before.'<ul class="groups">'.$ret.'</ul>'.$after;
		
		if ($echo == true)
			echo $ret;
		else
			return $ret;	
	}
	
	/**
	 * Returns all events in a certain state
	 * For date selection, you can specify a start and/or an end timestamp.
	 * If both dates are specified, all events are returned, which are valid
	 * between this two dates in mode `ALL` (can start before and end after the
	 * corresponding dates. In mode `START` only events are returned, which start
	 * between this two dates. In mode `END` only events are returned, which end
	 * between this two dates. 
	 * If only a start date is spefied, all Events are returned, which are valid
	 * after this date in mode `ALL` and all events are returned, which start
	 * after this date in mode `START`. Mode `END` corresponds to `ALL`.
	 * If only a end date is specified, all events are returned, which are valid
	 * before this date in mode `ALL` and all events are returnd, which end
	 * before this date in mode `END`. Mode `START` corresponds to `ALL`.
	 * @param $filter using the following keys: id_inc, id_exc, author, state, categories, datefrom, dateto, datemode
	 * @param $sort_string Sort string
	 * @return Array of event IDs
	 */
	function getEvents($filter, $sort_string = '', $limit = 0, $start = 0, $count = false) {
		global $wpdb;
				
		if (empty($sort_string)) {
			$sort_string = 'e.tsfrom ASC';
		}
		
		// If its an allday event, modify any selection time, because allday events allways starts at 00:00
		if (isset($filter['allday']) && $filter['allday'] == true) {
			if (isset($filter['datefrom'])) {
				$df = $filter['datefrom'];
				$filter['datefrom'] = mktime(0,0,0,date('m', $df), date('d', $df), date('Y', $df));
			}
			if (isset($filter['dateto'])) {
				$df = $filter['dateto'];
				$filter['dateto'] = mktime(0,0,0,date('m', $df), date('d', $df)+1, date('Y', $df)) - 1;
			} 
		}
		
		$where = ' WHERE ';
		if (isset($filter['id_inc']) && is_array($filter['id_inc']))
			$where .= " e.eventid IN (".implode(',', $filter['id_inc']).") AND";
		if (isset($filter['id_exc']) && is_array($filter['id_exc']))
			$where .= " e.eventid NOT IN (".implode(',', $filter['id_exc']).") AND";
		if (isset($filter['state']) && isset(self::$valid_states[$filter['state']]))
			$where .= " e.state='{$filter['state']}' AND";
		if (isset($filter['author']))
			$where .= " e.author='{$filter['author']}' AND";
		if (isset($filter['allday']))
			$where .= " e.allday=".($filter['allday'] === true ? '1' : '0')." AND";
		if (isset($filter['datefrom']) || isset($filter['dateto'])) {
			
			if (!isset($filter['datemode'])) {
				$filter['datemode']	= FSE_DATE_MODE_ALL;
			}
			
			// Make date selection complete
			if (!isset($filter['datefrom']))
				$filter['datefrom'] = 0;
			if (!isset($filter['dateto']))
				$filter['dateto'] = mktime(23, 59, 59, 12, 31, 2037);
		
			// Allday events to-stamp is at the beginning of the day!
			$date_to_allday = mktime(0, 0, 0, date('m', $filter['datefrom']), date('d', $filter['datefrom']), date('y', $filter['datefrom']));
				
			// Events must always start before the end and 
			// must end after start
			$where .= ' (e.tsfrom <= '.$filter['dateto'].') AND '.
					  ' ((e.tsto >= '.$filter['datefrom'].') OR (e.tsto >= '.$date_to_allday.' AND allday = 1))'; 
				
			// 
			if ($filter['datemode'] == FSE_DATE_MODE_START) {
				$where .= ' AND (e.tsfrom >= '.$filter['datefrom'].')';
			} elseif ($filter['datemode'] == FSE_DATE_MODE_END) {
				$where .= ' AND (e.tsfrom <= '.$filter['dateto'].')';
			}
			
			$where .= ' AND';
		}
		
		// Join for categories
		$join = '';
		if (isset($filter['categories'])) {
			$f = $filter['categories'];
			if (!is_array($f))
				$f = array($f);
			$in = '';
			foreach($f as $c) {
				$c = intval($c);
				if (!empty($c))
					$in .= $c.',';
			}
			
			if (!empty($in)) {
				$in = substr($in, 0, strlen($in)-1);
				$where .= ' c.catid IN ('.$in.') AND';
				$join = ' LEFT JOIN '.$wpdb->prefix.'fsevents_cats AS c ON e.eventid = c.eventid ';
			}
		}

		if ($where != ' WHERE ')		
			$where = substr($where, 0, strlen($where) - 3);
		else 
			$where = '';	
			
		// Special Case 'Count'!
		if ($count == true) {
			$sql = 'SELECT DISTINCT count(e.eventid) FROM '.$wpdb->prefix.'fsevents AS e '.$join.$where.' ORDER BY '.$sort_string;
			return $wpdb->get_var($sql);
		} else {
			$sql = 'SELECT DISTINCT e.eventid FROM '.$wpdb->prefix.'fsevents AS e '.$join.$where.' ORDER BY '.$sort_string;
			if (!empty($limit)) {
				$sql .= ' LIMIT '.intval($start).', '.intval($limit);	
			}
		}
		
		$res = $wpdb->get_col($sql);
		
		if ($res === NULL)
			return false;	
		
		return $res;
	}
	
	/**
	 * Returns an array of events
	 * For more details see method getEvents of class fsCalendar
	 * @param $args:
	 * `echo` => boolean
	 * `number` => int; number of events to return
	 * `start` => int; start of selection
	 * `template` => string; output template
	 * `before` => string; print before
	 * `after` => string; print after
	 * `allwaysshowenddate` => boolean
	 * `include` = array of int; array of ids to include
	 * `exclude` => array of int; array of ids to exclude
	 * `author` => string; filter author
	 * `state`  => string; filter state
	 * `categories` => array; filter categories
	 * `datefrom` => timestamp; start
	 * `dateend` => timestamp; end
	 * `datemode` => int; datemode
	 * `orderby` => array; fields to sort
	 * `orderdir` => array; direction to sort foreach field (asc|desc)
	 * `groupby` => 'm','d','Y' Group in listoutput
	
	
	 * @return Array of events
	 * @see fsCalendar.getEvents()
	 */
	function getEventsExternal($args = array()) {
		$author = $dateto = $allday = '';
		$datemode = FSE_DATE_MODE_ALL;
		$state = 'publish';
		//$d = time();
		//$datefrom = mktime(0,0,0, date('m', $d), date('d', $d), date('Y', $d));
		$datefrom = mktime();
		$categories = $orderby = $orderdir = $include = $exclude = array();
		
		// Get some values from options
		$number = intval(get_option('fse_number'));
		
		foreach($args as $k => $a) {
			switch($k) {
				case 'number':
					$a = intval($a);
					$number = $a;
				case 'exclude':
					if (!is_array($a))
						$a = explode(',', $a);
					foreach($a as $e) {
						$e = intval($e);
						if (!empty($e)) {
							$exclude[] = intval($e);
						}
					}
					break;
				case 'include':
					if (!is_array($a))
						$a = explode(',', $a);
					foreach($a as $e) {
						$e = intval($e);
						if (!empty($e)) {
							$include[] = intval($e);
						}
					}
					break;
				case 'state':
					if (in_array($a,array('publish', 'draft')))
						$state = $a;
					break;
				case 'author':
					$a = intval($a);
					$u = new WP_User($a);
					if (!empty($u->ID)) {
						$author = $a;
					}
					break;
				case 'categories':
					if (!is_array($a))
						$a = explode(',', $a);
					foreach($a as $c) {
						$c = intval($c);
						if (!empty($c))
							$categories[] = $c; 
					}
					break;
				case 'datefrom':
					$a = intval($a);
					if ($a > 0)
						$datefrom = $a;
					break;
				case 'allday':
					if (is_bool($a)) {
						$allday = $a;
					}
					break;
				case 'dateto':
					$a = intval($a);
					if ($a > 0)
						$dateto = $a;
					break;
				case 'datemode':
					$a = intval($a);
					if (in_array($a, array(1,2,3)))
						$datemode = $a;
					break;
				case 'orderby':
					if (!is_array($a))
						$a = array($a);
					$orderby = $a;
					break;
				case 'orderdir':
					if (!is_array($a))
						$a = array($a);
					$orderdir = $a;
					break;
			}
		}
		
		$sortstring = '';
		if (count($orderby) > 0) {
			$dir = array('desc','asc','descending','ascending');
			foreach($orderby as $k => $o) {
				$o = trim(strtolower($o));
				if (in_array($o, self::$valid_fields)) {
					if (!empty($sortstring))
						$sortstring .= ', ';
					if (strpos($o, '.') === false)
						$sortstring .= 'e.';
					$sortstring .= $o;
					
					if (isset($orderdir[$k])) {
						$d = trim(strtolower($orderdir[$k]));
						if (in_array($d, $dir)) {
							$sortstring .= ' '.$d;
						}
					}
				}
			}
		}
		
		if (!empty($state))
			$filter['state'] = $state;
		if (!empty($author))
			$filter['author'] = $author;
		if (count($categories) > 0)
			$filter['categories'] = $categories;
		if (!empty($datefrom))
			$filter['datefrom'] = $datefrom;
		if (!empty($dateto))
			$filter['dateto'] = $dateto;
		if (count($include) > 0)
			$filter['id_inc'] = $include;
		if (count($exclude) > 0)
			$filter['id_exc'] = $exclude;
		if (is_bool($allday) == true) // Type!
			$filter['allday'] = $allday;
		$filter['datemode'] = $datemode;
				
		$evt = $this->getEvents($filter, $sortstring, $number);
		
		if ($evt === false) {
			return false;
		}
		$ret = array();
		foreach($evt as $e) {
			$et = new fsEvent($e, '', false);
			if ($et->eventid > 0) {
				$ret[] = $et;
			}
		}
		
		return $ret;
	}
	
	function userCanAddEvents() {
		return current_user_can(1);	
	}
	
	function userCanViewEvents() {
		return current_user_can(1);	
	}
	
	function userCanEditEvents() {
		return current_user_can(1);
	}
	
	function userCanPublishEvents() {
		return current_user_can(2);
	}
	
	function userCanDeleteEvents() {
		return current_user_can(1);
	}
	
	/**
	 * Adds all necessary options and creates the necessary tables
	 */
	function hookActivate() {
		global $wpdb;
		
		foreach(self::$plugin_options as $k => $v) {
			add_option($k, $v);
		}
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		
		$sql = "CREATE TABLE `".$wpdb->prefix."fsevents` (
			`eventid` INT NOT NULL AUTO_INCREMENT,
			`subject` VARCHAR(255) NOT NULL,
			`tsfrom` INT NOT NULL,
			`tsto` INT NOT NULL,
			`allday` TINYINT(1) NOT NULL DEFAULT '0',
			`description` TEXT NULL,
			`location` VARCHAR(255) NULL,
			`author` BIGINT NOT NULL,
			`createdate` INT NOT NULL,
			`publishauthor` BIGINT NOT NULL,
			`publishdate` INT NOT NULL,
			`state` VARCHAR(10) NOT NULL,
			PRIMARY KEY  (`eventid`)
			);";
		
		dbDelta($sql);
		
		$sql = "CREATE TABLE `".$wpdb->prefix."fsevents_cats` (
			`eventid` INT NOT NULL,
			`catid` BIGINT NOT NULL,
			PRIMARY KEY  (`eventid`, `catid`)
			);";
		
		dbDelta($sql);
	}

	/**
	 * Convert the Format String from php to fullcalender
	 * @see http://arshaw.com/fullcalendar/docs/utilities/formatDate/
	 * @param $fmt
	 */
	function convertDateFmt($fmt) {
		$arr_rules = array('a'=>'tt',
						 'A'=>'TT',
						 'B'=>'',
						 'c'=>'u',
						 'd'=>'dd',
						 'D'=>'ddd',
						 'F'=>'MMMM',
						 'g'=>'h',
						 'G'=>'H',
						 'h'=>'hh',
						 'H'=>'HH',
						 'i'=>'mm',
						 'I'=>'',
						 'j'=>'d',
						 'l'=>'dddd',
						 'L'=>'',
						 'm'=>'MM',
						 'M'=>'MMM',
						 'n'=>'M',
						 'O'=>'',
						 'r'=>'',
						 's'=>'ss',
						 'S'=>'S',
						 't'=>'',
						 'T'=>'',
						 'U'=>'',
						 'w'=>'',
						 'W'=>'',
						 'y'=>'yy',
						 'Y'=>'yyyy',
						 'z'=>'',
						 'Z'=>'');
		$ret = '';
		for ($i=0; $i<strlen($fmt); $i++) {
			if (isset($arr_rules[$fmt[$i]])) {
				$ret .= $arr_rules[$fmt[$i]];
			} else {
				$ret .= $fmt[$i];	
			}
		}
		return $ret;
	}
	
	/**
	 * Deletes the announcement page and all options
	 */
	function hookUninstall()  {
		// Remove all options
		foreach(self::$plugin_options as $k => $v) {
			remove_option($k);
		}
	}
}

if (class_exists('fsCalendar')) {
	$fsCalendar = new fsCalendar();
}
?>