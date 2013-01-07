<?php
/*
 Plugin Name: WP Calendar
 Plugin URI: http://www.faebusoft.ch/webentwicklung/wpcalendar/
 Description: WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage.
 Author: Fabian von Allmen
 Author URI: http://www.faebusoft.ch
 Version: 1.5.3
 License: GPL
 Last Update: 2013-01-07
 */

define('FSE_DATE_MODE_ALL', 1); // Event is valid in the interval
define('FSE_DATE_MODE_START', 2); // Event starts in the interval
define('FSE_DATE_MODE_END', 3); // Event ends in the interval

define('FSE_GROUPBY_NONE', ''); // Event grouping by day
define('FSE_GROUPBY_DAY', 'd'); // Event grouping by day
define('FSE_GROUPBY_MONTH', 'm'); // Event grouping by month
define('FSE_GROUPBY_YEAR', 'y'); // Event grouping by year

define('FSE_META_EVENT_ID', 'event_id');
define('FSE_META_EVENT_ID_TEMP', 'event_id_temp');

define('FSE_DB_VERSION', 5);

define('FSE_SQL_DEBUG', false);

require_once('fsCalendarSettings.php');
require_once('fsCalendarAdmin.php');
require_once('fsCalendarEvent.php');
require_once('fsCalendarWidgets.php');
require_once('fsCalendarFunctions.php');

class fsCalendar {

	static $plugin_name     = 'Calendar';
	static $plugin_vers     = '1.5.3';
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
	static $gmt_offset      = -1;

	static $valid_fields = array('eventid',
	 	 'subject', 
		 'tsfrom', 
		 'tsto', 
	     'datefrom', 
	     'dateto',
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
	 var $load_fc_libs = false;

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
			'fse_template_lst' => '<strong><a href="{event_url}">{event_subject}</a></strong><br />{event_startdate} {event_starttime} - {event_enddate} {event_endtime} @ {event_location}',
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
			'fse_allday_hide_time'=>1,
			'fse_pagination'=>0,
			'fse_pagination_usedots'=>1,
			'fse_pagination_prev_text'=>'&laquo;',
			'fse_pagination_next_text'=>'&raquo;',
			'fse_pagination_end_size'=>3,
			'fse_pagination_mid_size'=>3,
			'fse_gmt_hack'=>0,
			'fse_load_fc_libs'=>1,
	 		'fse_adm_default_start_time'=>'',
	 		'fse_adm_default_end_time'=>''
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
	 	add_action('init',                  array(&$this, 'hookInit'));
	 	add_action('init',                  array(&$this, 'hookRegisterScripts'));
	 	add_action('init',                  array(&$this, 'hookRegisterStyles'));
	 	add_action('widgets_init', 		    array(&$this, 'hookRegisterWidgets'));

	 	add_action('wp_ajax_nopriv_wpcal-getevents', array(&$this, 'hookAjaxGetEvents'));
	 	add_action('wp_ajax_wpcal-getevents', array(&$this, 'hookAjaxGetEvents'));

	 	add_action('comment_form_top',      array(&$this, 'hookAddEventToCommentForm'));
	 	add_action('comment_post',          array(&$this, 'hookSaveCommentMeta'), 1, 2);
	 	add_action('wp_set_comment_status', array(&$this, 'hookUpdateCommentMeta'), 1, 2);

	 	add_filter('the_title',             array(&$this, 'hookFilterTitle'), 99, 2);
	 	add_filter('wp_title',              array(&$this, 'hookFilterPageTitle'));
	 	add_filter('the_content',           array(&$this, 'hookFilterContent'));
	 	add_filter('get_pages',             array(&$this, 'hookHidePageFromSelection'));
	 	add_filter('comments_array',        array(&$this, 'hookFilterCommentEvents'));
	 	add_filter('get_comments_number',   array(&$this, 'hookFilterCommentEventsCount'));
	 	add_filter('comment_post_redirect', array(&$this, 'hookFixCommentPostRedirection'));
	 	add_filter('page_link',             array(&$this, 'hookFixEventPageLink'), 99, 2);

	 	register_activation_hook(__FILE__,  array(&$this, 'hookActivate'));
	 	register_uninstall_hook(__FILE__,   array('fsCalendar', 'hookUninstall')); // Static

	 	// Init Admin
	 	if (is_admin()) {
	 		$this->admin = new fsCalendarAdmin();
	 		
	 		$this->upgradeDataBase( );
	 	}
	 }

	 /**
	  * Initialize some vars
	  * @return void
	  */
	 function hookInit() {
	 	load_plugin_textdomain(self::$plugin_textdom, false, self::$plugin_lang_dir);
	 	self::$valid_states    = array('draft'=>__('Draft', self::$plugin_textdom),
		   'publish'=>__('Published', self::$plugin_textdom), ''=>'-');


	 	if (strpos($_SERVER['REQUEST_URI'], '/feed/ical') !== false) {

	 		$args = array();
	 		if (isset($_GET['categories'])) {
	 			$args['categories'] = $_GET['categories'];
	 		}

	 		fse_print_events_feed($args);
	 		exit;
	 	}
	 }

	 /**
	  * Register Styles to Load
	  * @return void
	  */
	 function hookRegisterStyles() {
	 	if (!is_admin() && get_option('fse_load_fc_libs') == true) {
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
	 	if (!is_admin() && get_option('fse_load_fc_libs') == true) {
	 		if (get_option('fse_load_jquery') == true)
	 		wp_enqueue_script('jquery');
	 		if (get_option('fse_load_jqueryui') == true)
	 		wp_enqueue_script('jquery-ui-core');

	 		wp_enqueue_script('fullcalendar', self::$plugin_js_url.'fullcalendar.min.js');
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
	 	global $comment;

	 	// Make sure, that the titles are not filtered in admin interface
	 	$req = $_SERVER['REQUEST_URI'];

	 	if (strpos($req, 'edit-pages.php') === false &&
	 	strpos($req, 'edit.php?post_type=page') === false) {

	 		// If we have a comment for the post (comment lists)
	 		// read the meta data and enrich the title, to make sure tags
	 		// are evaluated correctly
	 		if (!isset($_GET['event']) && isset($comment) && $comment->comment_post_ID == $postid) {
	 			$com_event_id = get_comment_meta($comment->comment_ID, FSE_META_EVENT_ID, true);
	 			if (!empty($com_event_id) && strpos($title, '{event_id') === false) {
	 				$title = '{event_id; id='.intval($com_event_id).'}'.$title.' ('.__('Event').')';
	 			}
	 		}

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
	 	$args['number']   = 0; // Do not limit!

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
	 		$e['post_id'] = $evt->postid;
	 		$e['post_url'] = (empty($evt->postid) ? '' : get_permalink($evt->postid));
	 		$e['title'] = $evt->subject;
	 		$e['allDay'] = ($evt->allday == true ? true : false);
	 		$e['start'] = mysql2date('c', $evt->from);
	 		$e['end'] = mysql2date('c', $evt->to);
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
	  * If the comment is for the events detail page, it saves the relation
	  * to the event id
	  * @param $comment_id
	  */
	 function hookSaveCommentMeta($comment_id, $approved) {

	 	// Event Id beschaffen zum Kommentar
	 	if (isset($_POST['fse_eventid'])) {
	 		$eventid = intval($_POST['fse_eventid']);
	 	} else {
	 		return;
	 	}

	 	if ($approved == '1') {
	 		add_comment_meta($comment_id, FSE_META_EVENT_ID, $eventid, true);
	 	} else {
	 		add_comment_meta($comment_id, FSE_META_EVENT_ID_TEMP, $eventid, true);
	 	}
	 }

	 function hookUpdateCommentMeta($comment_id, $status) {

	 	$eventid = get_comment_meta($comment_id, FSE_META_EVENT_ID, $eventid, true);
	 	if (!empty($eventid)) {
	 		$approved_old = true;
	 	} else {
	 		$eventid = get_comment_meta($comment_id, FSE_META_EVENT_ID_TEMP, $eventid, true);
	 		if (!empty($eventid)) {
	 			$approved_old = false;
	 		}
	 	}

	 	if (empty($eventid)) {
	 		return;
	 	}

	 	if (is_array($eventid)) {
	 		$eventid = $eventid[0];
	 	}


	 	// Action is approved, when manually done by admin, if comment is restored from
	 	// trash or spam, the approve state from the DB ist in the state [0/1]
	 	$approved_new = ($status == 'approve' || $status == '1');
	 	if ($approved_new == true && $approved_old == false) {
	 		add_comment_meta($comment_id, FSE_META_EVENT_ID, $eventid, true);
	 		delete_comment_meta($comment_id, FSE_META_EVENT_ID_TEMP);
	 	} elseif ($approved_new == false && $approved_old == true) {
	 		add_comment_meta($comment_id, FSE_META_EVENT_ID_TEMP, $eventid, true);
	 		delete_comment_meta($comment_id, FSE_META_EVENT_ID);
	 	}
	 }

	 function hookAddEventToCommentForm($fields) {
	 	if (isset($_GET['event'])) {
	 		the_comment_event_meta();
	 	}
	 }

	 /**
	  * Filters all comments just for the current event
	  * @param $comments
	  * @param $postid
	  */
	 function hookFilterCommentEvents($comments) {
	 	if (!isset($_GET['event'])) {
	 		return $comments;
	 	} else {
	 		$event_id = intval($_GET['event']);
	 	}

	 	$new_comments = array();
	 	foreach($comments as $comment) {
	 		$com_event_id = get_comment_meta($comment->comment_ID, FSE_META_EVENT_ID, true);
	 		if (empty($com_event_id) || $com_event_id == $event_id) {
	 			$new_comments[] = $comment;
	 		}
	 	}
	 	return $new_comments;
	 }

	 /**
	  * Fixes the number of comments for the current event
	  * @param $count
	  * @param $post_id
	  */
	 function hookFilterCommentEventsCount($count) {
	 	global $wp_query;

	 	if (isset($_GET['event'])) {
	 		return count($wp_query->comments);
	 	} else {
	 		return $count;
	 	}
	 }


	 function hookFixCommentPostRedirection($location) {
	 	if (strpos($location, '&event=') > 0 ||
	 	strpos($location, '?event=') > 0) {
	 		return $location;
	 	}

	 	if (isset($_GET['event'])) {
	 		$event_id = $_GET['event'];
	 	} elseif (isset($_POST['fse_eventid'])) {
	 		$event_id = $_POST['fse_eventid'];
	 	} else {
	 		return $location;
	 	}

	 	$hash = '';
	 	if (($pos = strpos($location, '#')) > 0) {
	 		$hash = substr($location, $pos);
	 		$location = substr($location, 0, $pos);
	 	}

	 	if (strpos($location, '?') > 0) {
	 		$location .= '&event='.$event_id;
	 	} else {
	 		$location .= '?event='.$event_id;
	 	}
	 	return $location.$hash;
	 }

	 function hookFixEventPageLink($link, $pageid) {
	 	global $comment;

	 	if (!isset($_GET['event']) && isset($comment->comment_post_ID) && $comment->comment_post_ID == $pageid &&
	 	strpos($link,'&event=') === false && strpos($link, '?event=') == false) {
	 		$com_event_id = get_comment_meta($comment->comment_ID, FSE_META_EVENT_ID, true);
	 		if (!empty($com_event_id)) {
	 			$hash = '';
	 			if (($pos = strpos($link, '#')) > 0) {
	 				$hash = substr($link, $pos);
	 				$link = substr($link, 0, $pos);
	 			}

	 			if (strpos($link, '?') > 0) {
	 				$link .= '&event='.$com_event_id;
	 			} else {
	 				$link .= '?event='.$com_event_id;
	 			}
	 			$link .= $hash;
	 		}
	 	}

	 	return $link;
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

	 	$showenddate = get_option('fse_show_enddate') == true ? true : false;
	 	$hideifallday = get_option('fse_allday_hide_time') == true ? true : false;

	 	if (!empty($evt)) {
	 		// We just create an event object, if it does no exist, all var are empty!

	 	} elseif (isset($_GET['event'])) {
	 		$evt = new fsEvent(intval($_GET['event']), 'publish');
	 	} else {
	 		// Load an empty event, to get all attributes in the correct format
	 		$evt = new fsEvent(-1);
	 	}

	 	// Calculate duration
	 	$start = floor(mysql2date('U', $evt->from)/60);
	 	$end   = floor(mysql2date('U', $evt->to)/60);
	 	$diff  = $end - $start;


	 	if ($evt->allday == true) {
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
	 				list($opt_orig, $val) = explode('=', $token[$i], 2);

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
	 				if (isset($opts['truncate_more'])) {
	 					$rep = $evt->getDescription($opts['truncate_more']);
	 				} else {
	 					$rep = $evt->getDescription();
	 				}
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
	 				if (!empty($evt->from) && $evt->from != '0000-00-00 00:00:00') {
	 					if (isset($opts['fmt']))
	 					$rep = $evt->getStart($opts['fmt'], 2);
	 					else
	 					$rep = $evt->getStart('', 2);
	 				} else {
	 					$rep = '';
	 				}
	 				break;
	 			case 'enddate':
	 				if (!empty($evt->to) && $evt->to != '0000-00-00 00:00:00') {
	 					if (isset($opts['alwaysshowenddate']))
	 					$l_sed = ($opts['alwaysshowenddate'] == true ? true : false);
	 					else
	 					$l_sed = $showenddate;

	 					// Do not display if date AND time is the same
	 					if ($l_sed == false &&
	 					( mysql2date('d', $evt->to) == mysql2date('d', $evt->from) &&
	 					mysql2date('m', $evt->to) == mysql2date('m', $evt->from) &&
	 					mysql2date('Y', $evt->to) == mysql2date('Y', $evt->from) )) {
	 						$rep = '';
	 					} else {

	 						if (isset($opts['before']))
	 						$rep = $opts['before'];
	 						else
	 						$rep = '';

	 						if (isset($opts['fmt']))
	 						$rep .= $evt->getEnd($opts['fmt'], 2);
	 						else
	 						$rep .= $evt->getEnd('', 2);
	 					}
	 				} else {
	 					$rep = '';
	 				}
	 				break;
	 			case 'starttime':
	 				if (!empty($evt->from) && $evt->from != '0000-00-00 00:00:00') {
	 					if (isset($opts['hideifallday']))
	 					$l_hide = ($opts['hideifallday'] == true ? true : false);
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
	 				if (!empty($evt->to) && $evt->to != '0000-00-00 00:00:00') {
	 					if (isset($opts['hideifallday']))
	 					$l_hide = ($opts['hideifallday'] == true ? true : false);
	 					else
	 					$l_hide = $hideifallday;

	 					// Do not display if date AND time is the same
	 					if (isset($opts['alwaysshowenddate']))
	 					$l_sed = ($opts['alwaysshowenddate'] == true ? true : false);
	 					else
	 					$l_sed = $showenddate;

	 					if (($evt->allday == true && $l_hide == true) ||
	 					($l_sed == false && $evt->from == $evt->to)) {
	 						$rep = '';
	 					} else {

	 						if (isset($opts['before']))
	 						$rep = $opts['before'];
	 						else
	 						$rep = '';

	 						if (isset($opts['fmt']))
	 						$rep .= $evt->getEnd($opts['fmt'], 3);
	 						else
	 						$rep .= $evt->getEnd('', 3);
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
	 					if ($evt->allday == true) {
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
	 				if (!empty($evt->publishdate) && $evt->publishdate != '0000-00-00 00:00:00') {
	 					if (isset($opts['fmt']))
	 					$rep = mysql2date($opts['fmt'], $evt->publishdate);
	 					else
	 					$rep = mysql2date('d.m.Y', $evt->publishdate);
	 				} else {
	 					$rep = '';
	 				}
	 				break;
	 			case 'publishtime':
	 				if (!empty($evt->publishdate) && $evt->publishdate != '0000-00-00 00:00:00') {
	 					if (isset($opts['fmt']))
	 					$rep = mysql2date($opts['fmt'], $evt->publishdate);
	 					else
	 					$rep = mysql2date('H:i', $evt->publishdate);
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
	 				if (isset($opts['linktopost']) && $opts['linktopost'] == true && !empty($evt->postid)) {
	 					$rep = get_permalink($evt->postid);
	 				} elseif (!empty($page_url) && !empty($evt->eventid)) {
	 					$rep = $page_url.$evt->eventid;
	 				} else {
	 					$rep = '';
	 				}
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
	 					if (get_option('fse_ws_wp') == true) {
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
							"prev: '".__('&nbsp;&#9664;&nbsp;', self::$plugin_textdom)."',".
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

	 				if (!isset($opts_orig['eventClick'])) {
	 					// Link Click
	 					$postlink = (isset($opts_orig['linktopost']) && $opts_orig['linktopost'] == true);
	 					if ($postlink || isset($page_url)) {
	 						$rep .= "eventClick: function(calEvent, jsEvent, view) {";
	 						if ($postlink) {
	 							$rep .= "if (calEvent.post_url != '') { document.location.href=calEvent.post_url; return; }";
	 						}
	 						if (!empty($page_url)) {
	 							$rep .= "document.location.href='$page_url'+calEvent.id;";
	 						}
	 						$rep .= "},";
	 					}
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
	 		if (substr($val,0,1) == '[' || substr($val,0,1) == '{') {
	 			$ret .= $val;
	 		} else {
	 			$ret .= (is_numeric($val) ? '' : '"').$val.(is_numeric($val) ? '' : '"');
	 		}
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

	 	if (isset($_GET['wpcal-page'])) {
	 		$args['page'] = intval($_GET['wpcal-page']);
	 	} else {
	 		$args['page'] = 1;
	 	}
	 	$pagination = get_option('fse_pagination');

	 	foreach($args as $k => $a) {
	 		switch($k) {
	 			case 'echo':
	 				$echo = ($a == true ? true : false);
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
	 				$showend = ($a == true ? true : false);
	 				break;
	 			case 'pagination':
	 				$pagination = ($a == true ? true : false); // Allow type cast using == instead of ===$
	 				break;
	 		}
	 	}

	 	$ret = '';
	 	$evt = $this->getEventsExternal($args);

	 	if ($pagination) {
	 		$args['count'] = true;
	 		$count = $this->getEventsExternal($args);

	 		// If no pagination is needed, disabled it
	 		if (count($evt) == $count) {
	 			$pagination = false;
	 		}
	 	}

	 	foreach($evt as $e) {
	 		$ret .= $this->filterContent($template, $e);
	 	}

	 	$pagstr = '';
	 	if ($pagination) {
	 		$pagstr = $this->getEventsPagination($count, $args);
	 	}

	 	$ret = $pagstr.$before.$ret.$after.$pagstr;

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

	 	$pagination = get_option('fse_pagination');

	 	if (isset($_GET['wpcal-page'])) {
	 		$args['page'] = intval($_GET['wpcal-page']);
	 	} else {
	 		$args['page'] = 1;
	 	}

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
	 				if (in_array($a, array('','d','m','y')))
	 				$groupby = $a;
	 				break;
	 			case 'groupby_header':
	 				$groupby_hdr = $a;
	 				break;
	 			case 'pagination':
	 				$pagination = ($a == true ? true : false); // Allow type cast using == instead of ===$
	 				break;

	 		}
	 	}

	 	// Sort must be by date, the user can choos, if asc or desc...
	 	if (isset($filter['orderby'])) {
	 		unset($filter['orderby']);
	 	}
	 	$filter['orderby'] = array('datefrom');

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


	 	if ($pagination) {
	 		$args['count'] = true;
	 		$count = $this->getEventsExternal($args);

	 		// If no pagination is needed, disabled it
	 		if (count($evt) == $count) {
	 			$pagination = false;
	 		}
	 	}

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

	 	$pagstr = '';
	 	if ($pagination) {
	 		$pagstr = $this->getEventsPagination($count, $args);
	 	}

	 	$ret = $pagstr.$before.'<ul class="groups">'.$ret.'</ul>'.$after.$pagstr;

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
	 		$sort_string = 'e.`datefrom` ASC';
	 	}
	 	
	 	// Convert timestamps to mysql date format
	 	if (isset($filter['datefrom']) && !empty($filter['datefrom'])) {
	 		// Neu auch text unterstützen
	 		if (strpos(strtolower($filter['datefrom']), 'now') !== false) { // Now
	 			$filter['datefrom'] = $this->calculate_dates($filter['datefrom']);
	 		} else if (strpos(strtolower($filter['datefrom']), 'today') !== false) { // Today
	 			$filter['datefrom'] = $this->calculate_dates($filter['datefrom']);
	 		} else if (strpos($filter['datefrom'], '-') === false) { // Integer format
	 			$filter['datefrom'] = date('Y-m-d H:i:s', $filter['datefrom']);
	 		} else {
	 			// Validate!
	 			if (strtotime($filter['datefrom']) == false || strtotime($filter['datefrom']) == -1) {
	 				$filter['datefrom'] = $this->calculate_dates('now');
	 			}
	 		}
	 	}
	 	if (isset($filter['dateto']) && !empty($filter['dateto'])) {
	 		if (strpos(strtolower($filter['dateto']), 'now') !== false) {
	 			$filter['dateto'] = $this->calculate_dates($filter['dateto']);
	 		} else if (strpos(strtolower($filter['dateto']), 'today') !== false) {
	 			$filter['dateto'] = $this->calculate_dates($filter['dateto'], true);
	 		} else if (strpos($filter['dateto'], '-') === false) {
	 			$filter['dateto'] = date('Y-m-d H:i:s', $filter['dateto']);
	 		} else {
	 			// Validate!
	 			if (strtotime($filter['dateto']) == false || strtotime($filter['dateto']) == -1) {
	 				unset($filter['dateto']);
	 			}
	 		}
	 	}

	 	// If its an allday event, modify any selection time, because allday events allways starts at 00:00
	 	if (isset($filter['allday']) && $filter['allday'] == true) {
	 		if (isset($filter['datefrom'])) {
	 			$df = $filter['datefrom'];
	 			$filter['datefrom'] = date('Y-m-d H:i:s', mktime(0,0,0,mysql2date('m', $df), mysql2date('d', $df), mysql2date('Y', $df)));
	 		}
	 		if (isset($filter['dateto'])) {
	 			$df = $filter['dateto'];
	 			$filter['dateto'] = date('Y-m-d H:i:s', mktime(0,0,0,mysql2date('m', $df), mysql2date('d', $df)+1, mysql2date('Y', $df)) - 1);
	 		}
	 	}

	 	$where = ' WHERE ';
	 	if (isset($filter['id_inc']) && is_array($filter['id_inc']))
	 	$where .= " e.`eventid` IN (".implode(',', $filter['id_inc']).") AND";
	 	if (isset($filter['id_exc']) && is_array($filter['id_exc']))
	 	$where .= " e.`eventid` NOT IN (".implode(',', $filter['id_exc']).") AND";
	 	if (isset($filter['state']) && isset(self::$valid_states[$filter['state']]))
	 	$where .= " e.`state`='{$filter['state']}' AND";
	 	if (isset($filter['author']))
	 	$where .= " e.`author`='{$filter['author']}' AND";
	 	if (isset($filter['allday']))
	 	$where .= " e.`allday`=".($filter['allday'] === true ? '1' : '0')." AND";
	 	if (isset($filter['datefrom']) || isset($filter['dateto'])) {

	 		if (!isset($filter['datemode'])) {
	 			$filter['datemode']	= FSE_DATE_MODE_ALL;
	 		}

	 		// Make date selection complete
	 		if (!isset($filter['datefrom']) || empty($filter['datefrom']))
	 		$filter['datefrom'] = date('Y-m-d H:i:s', 0);
	 		if (!isset($filter['dateto']) || empty($filter['dateto']))
	 		$filter['dateto'] = date('Y-m-d H:i:s', mktime(23, 59, 59, 12, 31, 2037));

	 		// Allday events to-stamp is at the beginning of the day!
	 		$date_to_allday = date('Y-m-d H:i:s', mktime(0, 0, 0, mysql2date('m', $filter['datefrom']), mysql2date('d', $filter['datefrom']), mysql2date('y', $filter['datefrom'])));


	 		// Events must always start before the end and
	 		// must end after start
	 		$where .= ' (e.`datefrom` <= "'.$filter['dateto'].'") AND '.
  						' ((e.`dateto` >= "'.$filter['datefrom'].'") OR (e.`dateto` >= "'.$date_to_allday.'" AND `allday` = 1))'; 

	 		//
	 		if ($filter['datemode'] == FSE_DATE_MODE_START) {
	 			$where .= ' AND (e.`datefrom` >= "'.$filter['datefrom'].'")';
	 		} elseif ($filter['datemode'] == FSE_DATE_MODE_END) {
	 			$where .= ' AND (e.`datefrom` <= "'.$filter['dateto'].'")';
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
	 			$where .= ' c.`catid` IN ('.$in.') AND';
	 			$join = ' LEFT JOIN `'.$wpdb->prefix.'fsevents_cats` AS c ON e.`eventid` = c.`eventid` ';
	 		}
	 	}

	 	if ($where != ' WHERE ')
	 		$where = substr($where, 0, strlen($where) - 3);
	 	else
	 		$where = '';

	 	// Special Case 'Count'!
	 	if ($count == true) {
	 		$sql = 'SELECT DISTINCT count(e.`eventid`) FROM `'.$wpdb->prefix.'fsevents` AS e '.$join.$where.' ORDER BY '.$sort_string;
	 		return $wpdb->get_var($sql);
	 	} else {
	 		$sql = 'SELECT DISTINCT e.`eventid` FROM `'.$wpdb->prefix.'fsevents` AS e '.$join.$where.' ORDER BY '.$sort_string;
	 		if (!empty($limit)) {
	 			$sql .= ' LIMIT '.intval($start).', '.intval($limit);
	 		}
	 	}

	 	if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
	 		echo '<p>'.$sql.'</p>';
	 	}	
	 	
	 	$res = $wpdb->get_col($sql);

	 	if ($res === NULL)
	 		return false;

	 	return $res;
	 }

	 /**
	  * Creates the feedlink output
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
	 function getEventsFeed($args = array()) {
	 	$events = $this->getEventsExternal($args);

	 	$esc_chars = ",;\\";

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

	 	$feed = array();
	 	$feed[] = 'BEGIN:VCALENDAR';
	 	$feed[] = 'METHOD:PUBLISH';
	 	$feed[] = 'PRODID:http://www.faebusoft.ch/webentwicklung/wpcalendar/';
	 	$feed[] = 'VERSION:2.0';
	 	$feed[] = 'X-WR-TIMEZONE:'.get_option('timezone_string');

	 	//print_r($events);

	 	foreach($events as $e) {

	 		$feed[] = 'BEGIN:VEVENT';

	 		$feed[] = 'UID:'.get_bloginfo('url').'/feed/ical/'.$e->eventid;
	 		//$feed[] = 'UID:'.md5(uniqid());

	 		// Add description
	 		$feed[] = 'DESCRIPTION:'.str_replace(array("\r","\n"), array('','\n'),addcslashes(trim(strip_tags($e->getDescription())), $esc_chars));

	 		// Categories
	 		foreach($e->categories_t as $k => $c) {
	 			$e->categories_t[$k] = addcslashes($c, $esc_chars);
	 		}
	 		$feed[] = 'CATEGORIES:'.implode(',',$e->categories_t);

	 		// Location
	 		$feed[] = 'LOCATION:'.addcslashes($e->location, $esc_chars);

	 		// Summary
	 		$feed[] = 'SUMMARY:'.addcslashes($e->subject, $esc_chars);

	 		// Times
	 		if ($e->allday == true) {
	 			$feed[] = 'DTSTART;TZID='.get_option('timezone_string').';VALUE=DATE:'.mysql2date('Ymd', $e->from);

	 			// End has to be + 1!
	 			$end = strtotime($e->to)+(60*60*24);
	 			$feed[] = 'DTEND;TZID='.get_option('timezone_string').';VALUE=DATE:'.date('Ymd', $end);
	 		} else {
	 			$feed[] = 'DTSTART;TZID='.get_option('timezone_string').':'.mysql2date('Ymd\THis', $e->from);
	 			$feed[] = 'DTEND;TZID='.get_option('timezone_string').':'.mysql2date('Ymd\THis', $e->to);
	 		}

	 		// Classification
	 		$feed[] = 'CLASS:PUBLIC';

	 		// Publish Date of event
	 		$feed[] = 'DTSTAMP;TZID='.get_option('timezone_string').':'.mysql2date('Ymd\THis', $e->publishdate);

	 		// URL of event
	 		if (!empty($e->postid)) {
	 			$feed[] = 'URL:'.get_permalink($e->postid);
	 		} elseif (!empty($page_url)) {
	 			$feed[] = 'URL:'.$page_url.$e->eventid;
	 		}

	 		$feed[] = 'END:VEVENT';
	 	}

	 	$feed[] = 'END:VCALENDAR';

	 	// Now trim all date to maxium 75chars
	 	$output = '';
	 	foreach ($feed as $f) {
	 		$new_line = true;
	 		while(strlen($f) > 0) {
	 			if (!$new_line) {
	 				$output .= "\r\n "; // Add CRLF + Space!
	 			}
	 			$output .= substr($f, 0, 72);
	 			// String kürzen
	 			if (strlen($f) > 72) {
	 				$f = substr($f, 72);
	 				$new_line = false;
	 			} else {
	 				$f = '';
	 			}
	 		}
	 		$output .= "\r\n";
	 	}

	 	return $output;
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
	 	//$datefrom = mktime(0,0,0, fsCalendar::date('m', $d), fsCalendar::date('d', $d), fsCalendar::date('Y', $d));
	 	$datefrom = 'now';
	 	$categories = $orderby = $orderdir = $include = $exclude = array();
	 	$start = 0;
	 	$count = false;
	 	$page = 0;

	 	// Get some values from options
	 	$number = intval(get_option('fse_number'));

	 	foreach($args as $k => $a) {
	 		switch($k) {
	 			case 'number':
	 				$a = intval($a);
	 				if ($a >= 0) {
	 					$number = $a;
	 				}
	 				break;
	 			case 'start':
	 				$start = intval($a);
	 				break;
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
	 				$datefrom = $a;
	 				break;
	 			case 'allday':
	 				if (is_bool($a)) {
	 					$allday = $a;
	 				}
	 				break;
	 			case 'dateto':
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
	 			case 'count':
	 				$count = true;
	 				break;
	 			case 'page':
	 				$page = intval($a);
	 				break;
	 		}
	 	}

	 	// Calculate values for page
	 	if ($page > 0) {
	 		$start = ($page - 1) * $number;
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

	 	if ($count == true) {
	 		return $this->getEvents($filter, $sortstring, 0, 0, true);
	 	} else {
	 		$evt = $this->getEvents($filter, $sortstring, $number, $start);
	 	}


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

	 /**
	  * Return the HTML pagination String using the WP function paginate_links()
	  * @param $count Number of events
	  * @param $args  Argument (including page and pagination tags)
	  * @return The pagination HTML String
	  */
	 function getEventsPagination($count, $args) {
	 	$wp_args = array();

	 	$wp_args['base'] = preg_replace( '/(\?.*)?$/', '', $_SERVER["REQUEST_URI"] ).'%_%';
	 	$wp_args['format'] = '?wpcal-page=%#%';

	 	// Preserver other GET values
	 	foreach($_GET as $k => $v) {
	 		if (strtolower($k) != 'wpcal-page') {
	 			if (isset($wp_args['add_args']))
	 			$wp_args['add_args'] = array();
	 			$wp_args['add_args'][$k] = $v;
	 		}
	 	}

	 	$epp = 0;
	 	if (isset($args['number'])) {
	 		$epp = intval($args['number']);
	 	}
	 	if (empty($epp)) {
	 		$epp = intval(get_option('fse_number'));
	 	}

	 	if (isset($args['pagination_prev_text'])) {
	 		$wp_args['prev_text'] = $args['pagination_prev_text'];
	 	} else {
	 		$wp_args['prev_text'] = get_option('fse_pagination_prev_text');
	 	}

	 	if (isset($args['pagination_next_text'])) {
	 		$wp_args['next_text'] = $args['pagination_next_text'];
	 	} else {
	 		$wp_args['next_text'] = get_option('fse_pagination_next_text');
	 	}

	 	if (isset($args['pagination_end_size'])) {
	 		$wp_args['end_size'] = $args['pagination_end_size'];
	 	} else {
	 		$wp_args['end_size'] = get_option('fse_pagination_end_size');
	 	}

	 	if (isset($args['pagination_mid_size'])) {
	 		$wp_args['mid_size'] = $args['pagination_mid_size'];
	 	} else {
	 		$wp_args['mid_size'] = get_option('fse_pagination_mid_size');
	 	}

	 	if (isset($args['pagination_use_dots'])) {
	 		$wp_args['show_all'] = ($args['pagination_use_dots'] == true ? false : true);
	 	} else {
	 		$wp_args['show_all'] = get_option('fse_pagination_usedots') == true ? false : true;
	 	}

	 	$wp_args['prev_next'] = (!empty($wp_args['prev_text']) || !empty($wp_args['next_text']));

	 	// Calculate number of pages
	 	$wp_args['total'] = ceil($count / $epp);

	 	if ($args['page'] < 1)
	 	$wp_args['current'] = 1;
	 	elseif ($args['page'] > $wp_args['total'])
	 	$wp_args['current'] = $wp_args['total'];
	 	else
	 	$wp_args['current'] = $args['page'];

	 	return paginate_links($wp_args);
	 }

	 function calculate_dates($date, $endofday = false) {
		$date = strtolower($date);
		if (strpos($date, 'now') !== false) {
			$time = mktime();
		} elseif (strpos($date, 'today') !== false) {
			if (!$endofday) {
				$time = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
			} else {
				$time = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
			}
		} else {
			return $date;
		}
		
		$multi = 0;
		if (strpos($date, '+') !== false) {
			$multi = 1;
			list($dummy, $add) = explode('+', $date, 2);
			$add = intval($add);
		} elseif (strpos($date, '-') !== false) {
			$multi = -1;
			list($dummy, $add) = explode('-', $date, 2);
			$add = intval($add);	
		} else {
			return date('Y-m-d H:i:s', $time);
		}
		
		// Stunden hinzufügen
		$time += ($add*3600*$multi);
		return date('Y-m-d H:i:s', $time);
	 }
	 
	 function userCanAddEvents() {
	 	return current_user_can('edit_posts');
	 }

	 function userCanViewEvents() {
	 	return current_user_can('read');
	 }

	 function userCanEditEvents() {
	 	return current_user_can('edit_posts');
	 }

	 function userCanPublishEvents() {
	 	return current_user_can('publish_posts');
	 }

	 function userCanDeleteEvents() {
	 	return current_user_can('delete_posts');
	 }

	 /**
	  * Adds all necessary options and creates the necessary tables
	  */
	 function hookActivate() {
	 	global $wpdb;

	 	// Remove for debugging
	 	$wpdb->hide_errors( );
	 	
	 	foreach(self::$plugin_options as $k => $v) {
	 		add_option($k, $v);
	 	}

	 	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		$this->upgradeDataBase();

		$charset_collate = '';
	
		if ( ! empty($wpdb->charset) )
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		if ( ! empty($wpdb->collate) )
			$charset_collate .= " COLLATE $wpdb->collate";
	 	
	 	$sql = "CREATE TABLE ".$wpdb->prefix."fsevents (
			eventid INT NOT NULL AUTO_INCREMENT,
			subject VARCHAR(255) NOT NULL,
			datefrom DATETIME NULL,
			dateto DATETIME NULL,
			allday TINYINT(1) NOT NULL DEFAULT '0',
			description TEXT NULL,
			location VARCHAR(255) NULL,
			author BIGINT NOT NULL,
			createdaten DATETIME NULL,
			publishauthor BIGINT NULL,
			publishdaten DATETIME NULL,
			state VARCHAR(10) NOT NULL,
			recurring TINYINT(1) NOT NULL DEFAULT '0',
			rec_main_id INT NULL,
			postid BIGINT NULL,
			updatedbypost TINYINT NOT NULL DEFAULT '0',
			PRIMARY KEY  (eventid),
			KEY postid (postid)
			) $charset_collate;";

	 	dbDelta($sql);

	 	$sql = "CREATE TABLE ".$wpdb->prefix."fsevents_cats (
			eventid INT NOT NULL,
			catid BIGINT NOT NULL,
			PRIMARY KEY  (eventid,catid)
			) $charset_collate;";

	 	dbDelta($sql);
	 	
	 	// Save DB version (do again, because the upgrade will not save
	 	// the version, when the plugin is the first time activated!!!)
	 	update_option('fse_db_version', FSE_DB_VERSION);
	 }

	 function upgradeDataBase() {
	 	global $wpdb;
	 	
	 	$wpdb->hide_errors( );
	 	
	 	$vers = intval(get_option('fse_db_version'));
	 	
	 	if ($vers >= FSE_DB_VERSION || empty($vers)) {
	 		return;
	 	}
	 	
	 	// Migrate fields from/to to datefrom/dateto
	 	if ($vers < 4) {
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` ADD COLUMN `from` DATETIME NULL');
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` ADD COLUMN `to` DATETIME NULL');
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` ADD COLUMN `createdaten` DATETIME NULL');
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` ADD COLUMN `publishdaten` DATETIME NULL');
	 		
	 		$sql = 'SELECT `eventid`, `tsfrom`, `tsto`, `createdate`, `publishdate` FROM `'.$wpdb->prefix.'fsevents` WHERE `tsfrom` IS NOT NULL AND `from` IS NULL';

	 		$res = $wpdb->get_results($sql);

	 		foreach($res as $r) {
	 			$sql = 'UPDATE `'.$wpdb->prefix.'fsevents` '.
					'SET `from`="'.date('Y-m-d H:i:s', $r->tsfrom).'", '.
					'`to`="'.date('Y-m-d H:i:s', $r->tsto).'", '.
					'`createdaten`="'.date('Y-m-d H:i:s', $r->createdate).'" ';
	 			if (!empty($r->publishdate)) {
	 				$sql .= ', `publishdaten`="'.date('Y-m-d H:i:s', $r->publishdate).'" ';
	 			}
	 			$sql .= 'WHERE `eventid`='.$r->eventid;
	 			$wpdb->query($sql);
	 		}
	 		
	 		// Remove old columns
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `tsfrom`');
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `tsto`');
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `publishdate`');
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `createdate`');
	 	}
	 	
	 	if ($vers < 5) {
			// Just rename it		 		
	 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` CHANGE COLUMN `from` `datefrom` DATETIME NULL');
 			$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` CHANGE COLUMN `to` `dateto` DATETIME NULL');
 			
 			// Late Removal
 			if ($vers == 4) {
		 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `tsfrom`');
		 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `tsto`');
		 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `publishdate`');
		 		$wpdb->query('ALTER TABLE `'.$wpdb->prefix.'fsevents` DROP COLUMN `createdate`');
 			}
	 	}
	 	
	 	// Save DB version
	 	update_option('fse_db_version', FSE_DB_VERSION);
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
	 static function hookUninstall()  {
	 	// Remove all options
	 	foreach(self::$plugin_options as $k => $v) {
	 		delete_option($k);
	 	}
	 }
}

if (class_exists('fsCalendar')) {
	$fsCalendar = new fsCalendar();
}
?>