<?php
/*
Plugin Name: WP Calendar
Plugin URI: http://www.faebusoft.ch/downloads/calendar
Description: WP Calendar is an easy-to-use calendar plug-in to manage all your events with many options and a flexible usage.
Author: Fabian von Allmen
Author URI: http://www.faebusoft.ch
Version: 1.0.0 RC 1
License: GPL
Last Update: 25.09.2009
*/

define('FSE_DATE_MODE_ALL', 1); // Event is valid in the interval
define('FSE_DATE_MODE_START', 2); // Event starts in the interval
define('FSE_DATE_MODE_END', 3); // Event ends in the interval

define('FSE_GROUPBY_NONE', ''); // Event grouping by day
define('FSE_GROUPBY_DAY', 'd'); // Event grouping by day
define('FSE_GROUPBY_MONTH', 'm'); // Event grouping by month
define('FSE_GROUPBY_YEAR', 'y'); // Event grouping by year

class fsCalendar {
	const DAY_IN_SECONDS = 86400;

	private static $plugin_name     = 'Calendar';
	private static $plugin_vers     = '1.0.0';
	private static $plugin_id       = 'fsCal'; // Unique ID
	private static $plugin_options  = '';
	private static $plugin_filename = '';
	private static $plugin_dir      = '';
	private static $plugin_url      = '';
	private static $plugin_css_url  = '';
	private static $plugin_img_url  = '';
	private static $plugin_js_url   = '';
	private static $plugin_lang_dir = '';
	private static $plugin_textdom  = '';
	
	private static $valid_states;
	
	
	public static $valid_fields = array('eventid', 
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

	private $showAnnouncement = false;

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
									'fse_epp' => 15,
									'fse_page_create_notice' => 0
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
		add_action('init',                 array(&$this, 'hookRegisterTextDomain'));
		
		// Admin Hooks
		add_action('admin_menu',           array(&$this, 'hookAddAdminMenu'), 98);
		add_action('admin_menu',           array(&$this, 'hookOrderAdminMenu'), 99);
		add_action('admin_init',           array(&$this, 'hookRegisterScriptsAdmin'));
		add_action('admin_init',           array(&$this, 'hookRegisterStylesAdmin'));
		add_filter('plugin_action_links',  array(&$this, 'hookAddPlugInSettingsLink'), 10, 2 );
		add_filter('the_title',            array(&$this, 'hookFilterTitle'), 1, 2);
		add_filter('wp_title',             array(&$this, 'hookFilterPageTitle'));
		add_filter('the_content',          array(&$this, 'hookFilterContent'));
		add_filter('get_pages',            array(&$this, 'hookHidePageFromSelection'));

		register_activation_hook(__FILE__, array(&$this, 'hookActivate'));
		register_uninstall_hook(__FILE__,  array(&$this, 'hookUninstall'));
	}

	/**
	 * Load text domain
	 * @return void
	 */
	function hookRegisterTextDomain() {
		load_plugin_textdomain(self::$plugin_textdom, false, self::$plugin_lang_dir);
	}
	
	/**
	 * Register Styles to Load
	 * @return void
	 */
	function hookRegisterStyles() {
		wp_enqueue_style('thickbox');
	}
	
	/**
	 * Creates a menu entry in the settings menu
	 * @return void
	 */
	function hookAddAdminMenu() {
		/*$menutitle = '<img src="'.self::$plugin_img_url.'icon.png" alt=""> '.__('TB Announcement', self::$plugin_textdom);
		add_submenu_page('options-general.php', __('Thickbox Announcement', self::$plugin_textdom ), $menutitle, 8, self::$plugin_filename, array(&$this, 'createSettingsPage'));*/
		add_menu_page(__('Calendar', self::$plugin_textdom), __('Calendar', self::$plugin_textdom), 1, self::$plugin_filename, array(&$this, 'createCalendarPage')); // TODO: Replace User Level
		add_submenu_page(self::$plugin_filename, __('Calendar', self::$plugin_textdom), __('Edit', self::$plugin_textdom), 1, self::$plugin_filename, array(&$this, 'createCalendarPage'));
		add_submenu_page(self::$plugin_filename, __('Calendar', self::$plugin_textdom), __('Add new', self::$plugin_textdom), 1, self::$plugin_filename.'&action=new', array(&$this, 'createCalendarPage'));
		
		// Options
		$menutitle = '<img src="'.self::$plugin_img_url.'icon.png" alt=""> '.__('Calendar', self::$plugin_textdom);
		add_submenu_page('options-general.php', __('Calendar', self::$plugin_textdom), $menutitle, 8, self::$plugin_filename.'&action=settings', array(&$this, 'createCalendarPage'));
	}
	
	/**
	 * Changes the position of the created menu
	 * @return void
	 */
	function hookOrderAdminMenu() {
		global $menu;

		foreach($menu as $k => $m) {
			if ($m['2'] == self::$plugin_filename) {
				$mym = $m;
				unset($menu[$k]);
			} elseif ($m[2] == 'edit-comments.php') {
				$myi = $k;
			}
		}
		
		if (!isset($mym) || !isset($myi))
			return;
			
		$new_index = $myi + 1;

		
		// Make sure, no menu is overriden..
		if (isset($menu[$new_index])) {
			$corr = $new_index;
			for ($i=$new_index; true; $i+=5) {
				
				if (!isset($menu_tmp)) {
					$menu_tmp = $menu[$i];
				}
				
				// Wenn n�chster Index frei ist, dann raus
				if (!isset($menu[$i+1])) {
					$menu[$i+1] = $menu_tmp;
					break;
				} else {
					$menu_tmp2 = $menu[$i+1];
					$menu[$i+1] = $menu_tmp;
					
					$menu_tmp = $menu_tmp2;
				}
			}
		}
		
		$menu[$new_index] = $mym;
	}

	/**
	 * Loads all necesarry scripts for the settings page
	 * @return void
	 */
	function hookRegisterScriptsAdmin() {
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('fs-datepicker', self::$plugin_js_url.'ui.datepicker.js');
		wp_enqueue_script('fs-date', self::$plugin_js_url.'date.js');
		wp_enqueue_script(self::$plugin_id, self::$plugin_js_url.'helper.js');
		
		
		
		if (strpos($_SERVER['QUERY_STRING'], self::$plugin_filename) !== false && 
		   ($_GET['action'] == 'new' ||  $_GET['action'] == 'edit')) {
			wp_enqueue_script('post');
			if (user_can_richedit()) {
				wp_enqueue_script('editor');
				wp_enqueue_script('editor-functions');
				wp_enqueue_script('jquery-ui-tabs');
				wp_enqueue_script('media-upload');
				
				add_action('admin_print_footer_scripts', 'wp_tiny_mce', 25 );
			}
		}
	}

	/**
	 * Loads all necessary stylesheets for the admin interface
	 * @return void
	 */
	function hookRegisterStylesAdmin() {
		wp_enqueue_style('dashboard');
		wp_enqueue_style('thickbox');
		wp_enqueue_style('fs-styles-dp', self::$plugin_css_url.'jquery-ui-1.7.2.custom.css');
		wp_enqueue_style('fs-styles', self::$plugin_css_url.'default.css');
		wp_enqueue_style('wp-calendar', self::$plugin_css_url.'wpcalendar.css');
		
		/*if (strpos($_SERVER['QUERY_STRING'], self::$plugin_filename) !== false && 
		   ($_GET['action'] == 'new' || $_GET['action'] == 'edit')) {
			wp_enqueue_style('post');
		}*/
	}
	
	/**
	 * Adds a "Settings" link for this plug-in in the plug-in overview
	 * @return void
	 */
	function hookAddPlugInSettingsLink($links, $file) {
		if ($file == self::$plugin_filename) {
			array_unshift($links, '<a href="options-general.php?page='.$file.'">'.__('Settings', self::$plugin_textdom).'</a>');
		}
		return $links;
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
		
		
		if (strpos($req, 'edit-pages.php') === false) {
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
		$req = $_SERVER['REQUEST_URI'];
		if (strpos($req,'wp-admin/') !== false) {
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
	 * Creates the requested Calendar page
	 * @return unknown_type
	 */
	function createCalendarPage() {
		global $wpdb;
		global $user_ID;
		
		self::$valid_states    = array('draft'=>__('Draft', self::$plugin_textdom),
									   'publish'=>__('Published', self::$plugin_textdom)
								 );
		
		$action = $_GET['action'];
		
		
		
		switch ($action) {
			case 'new';
			case 'edit';
			case 'view';
				if ($action == 'edit' || $action == 'view') {
					$evt->eventid = intval($_GET['event']);
				} else {
					$evt->eventid = 0;
				}
				include('FormEvent.php');
				break;
			case 'settings';
				include('FormOptions.php');
				break;
			default:
				include('FormOverview.php');
				break;	
		}
	}
	
	/**
	 * Creates the Postbox for category selection
	 * @param $selected_cats
	 * @param $view
	 * @return unknown_type
	 */	
	function postBoxCategories($selected_cats, $view = false) {
	
		if ($view == false) {
			echo '<ul id="category-tabs">
				<li class="tabs"><a href="#categories-all" tabindex="3">'.__( 'All Categories' ).'</a></li>
				<li class="hide-if-no-js"><a href="#categories-pop" tabindex="3">'.__( 'Most Used' ).'</a></li>
			</ul>
			
			<div id="categories-pop" class="tabs-panel" style="display: none;">
				<ul id="categorychecklist-pop" class="categorychecklist form-no-clear" >';
			$popular_ids = wp_popular_terms_checklist('category');
				echo '</ul>
			</div>
			
			<div id="categories-all" class="tabs-panel">
				<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">';
				// Call it without any post id, but with selected categories instead!
				wp_category_checklist(0, false, $selected_cats, $popular_ids);
				echo '</ul>
			</div>';
			
			if ( current_user_can('manage_categories') ) {
				echo '<div id="category-adder" class="wp-hidden-children">
					<h4><a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3">'.__( '+ Add New Category' ).'</a></h4>
					<p id="category-add" class="wp-hidden-child">
					<label class="screen-reader-text" for="newcat">'.__( 'Add New Category' ).'</label><input type="text" name="newcat" id="newcat" class="form-required form-input-tip" value="'.esc_attr( 'New category name' ).'" tabindex="3" aria-required="true"/>
					<label class="screen-reader-text" for="newcat_parent">'.__('Parent category').':</label>';
				wp_dropdown_categories( array( 'hide_empty' => 0, 'name' => 'newcat_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => __('Parent category'), 'tab_index' => 3 ) );
					echo '<input type="button" id="category-add-sumbit" class="add:categorychecklist:category-add button" value="'.esc_attr( 'Add' ).'" tabindex="3" />';
				wp_nonce_field( 'add-category', '_ajax_nonce', false );
					echo '<span id="category-ajax-response"></span></p>
				</div>';
			}
		} else {
			$cats = get_categories(array('hide_empty'=>false));
			foreach($cats as $c) {
				$ca[$c->cat_ID] = $c->name;
			}
			$first = true;
			foreach($selected_cats as $c) {
				if ($first == true)
					$first = false;
				else
					echo ', ';
				echo $ca[$c];
			}
		}
	}
	
	/**
	 * Creates the filter and pagination bar in the overview
	 * @param $filter
	 * @param $part
	 * @param $page
	 * @param $epp
	 * @param $count
	 * @param $bl
	 * @return unknown_type
	 */
	function printNavigationBar($filter = array(), $part = 1, $page = 1, $epp = 0, $count = 0, $bl = '') {
		global $wpdb;
		?>
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action<?php echo ($part == 2 ? '2' : ''); ?>">
					<option selected="selected" value=""><?php _e('Choose action', self::$plugin_textdom); ?></option>
					<option value="delete"><?php _e('Delete', self::$plugin_textdom); ?></option>
					<?php 
					if ($this->userCanPublishEvents()) {
						echo '<option value="publish">'.__('Publish', self::$plugin_textdom).'</option>';
					}
					?>
					<?php 
					if ($this->userCanEditEvents()) {
						echo '<option value="draft">'.__('Set to Draft', self::$plugin_textdom).'</option>';
					}
					?>
				</select>
				<input id="doaction" class="button-secondary action" type="submit" name="doaction" value="<?php _e('Apply', self::$plugin_textdom); ?>" />
				<?php if ($part == 1) {?>
					<select name="event_start">
					<option value=""><?php _e('Show all dates', self::$plugin_textdom); ?></option>
					<?php 
					$min = $wpdb->get_var('SELECT MIN(tsfrom) AS min FROM '.$wpdb->prefix.'fsevents');
					$max = $wpdb->get_var('SELECT MAX(tsto)   AS max FROM '.$wpdb->prefix.'fsevents');
					if ($min != NULL && $max != NULL) {
						$ms = date('m', $min);
						$ys = date('Y', $min);
						$me = date('m', $max);
						$ye = date('Y', $max);
						
						while($ys <= $ye) {
							while($ms<=12 && ($ys < $ye || $ms <= $me)) {
								$time = mktime(0, 0, 0, $ms, 1, $ys);
								echo '<option value="'.$time.'"'.($time == $filter['datefrom'] ? ' selected="selected"' : '').'>'.date('M Y', $time).'</option>';
								$ms++;
							}
							$ms = 1;
							$ys++;
						}
					}
					?>
					</select>
					
					<?php 
					$dropdown_options = array('show_option_all' => __('View all categories'), 
											  'hide_empty' => 0, 
											  'hierarchical' => 1, 
											  'show_count' => 0, 
											  'name' => 'event_category', 
											  'orderby' => 'name', 
											  'selected' => $filter['categories'][0]);
					wp_dropdown_categories($dropdown_options);
					?>
					<input id="event-query-submit" class="button-secondary" type="submit" value="<?php _e('Filter', self::$plugin_textdom);?>" />
				<?php } ?>
			</div>
		<?php
		
		if ($count > $epp) {
			$evon = ($page - 1) * $epp + 1;
			$ebis = $page * $epp;
			$pages = ceil($count/$epp);
		?>
			<div class="tablenav-pages">		
			<span class="displaying-num"><?php printf('Showing %d-%d of %d', $evon, $ebis, $count); ?></span>
				<?php 
				if ($page > 1) 
					echo '<a class="prev page-numbers" href="'.$bl.'paged=1">&laquo;</a>'; 
				for($i=1; $i<=$pages; $i++) {
					if ($i == $page)
						echo '<span class="page-numbers current">'.$i.'</span>';
					else
						echo '<a class="page-numbers" href="'.$bl.'paged='.$i.'">'.$i.'</a>';
				} 
				if ($page < $pages)
					echo '<a class="next page-numbers" href="'.$bl.'paged='.$pages.'">&raquo;</a>';
				?>
			</div>
		<?php } ?>
		</div>
		<?php 
	}
	
	/**
	 * Filters all {event*} tags
	 * @param $content Content to filter
	 * @param $evt Event Object (optional)
	 * @return String Filtered content
	 */
	function filterContent($content, $evt = NULL, $showenddate = '') {
				
		preg_match_all('/\{event[s]?_(.+?)\}/', $content, $matches, PREG_SET_ORDER);
				
		if (count($matches) == 0) {
			return $content;
		}
		
		// Get Page url if any
		$page_id = intval(get_option('fse_page'));
		if (!empty($page_id)) {
			$page_url = get_permalink($page_id);
			if (!empty($page_url)) {
				if (strpos('?', $page_url) === false)
					$page_url .= '?event=';
				else
					$page_url .= '&event=';	
			}
		}
		
		// Load from options
		if ($showenddate == '') {
			$showenddate = get_option('fse_show_enddate') == 1 ? true : false;
		}
		
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
			$token = explode(';', $m[1]);
			unset($opts); // Reset options
			if (count($token) > 1) {
				for($i=1; $i<count($token); $i++) {
					list($opt, $val) = explode('=', $token[$i]);
					
					$val = trim($val);
					$opt = strtolower(trim($opt));
					
					// Remove " "
					preg_match('/^"(.*)"$/', $val, $matches);
					if (count($matches) > 0) {
						$val = $matches[1];
					}
					
					$opts[$opt] = $val;
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
					$rep = $evt->description;
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
							$rep = date($opts['fmt'], $evt->tsfrom);
						else
							$rep = date('d.m.Y', $evt->tsfrom);
					} else {
						$rep = '';
					}
					break;
				case 'enddate':
					if (!empty($evt->tsto)) {
						// Do not display if date AND time is the same
						if ($showenddate == false && 
							( date('d', $evt->tsto) == date('d', $evt->tsfrom) ||
							  date('m', $evt->tsto) == date('m', $evt->tsfrom) ||
							  date('Y', $evt->tsto) == date('Y', $evt->tsfrom) )) {
							$ret = '';
						} else {
							if (isset($opts['fmt']))
								$rep = date($opts['fmt'], $evt->tsto);
							else
								$rep = date('d.m.Y', $evt->tsto);
						}
					} else {
						$rep = '';	
					}
					break;
				case 'starttime':
					if (!empty($evt->tsfrom)) {
												// Do not display if date AND time is the same
						if ($showenddate == false && $evt->tsfrom == $evt->tsto) {
							$ret = '';
						} else {
							if (isset($opts['fmt']))
								$rep = date($opts['fmt'], $evt->tsfrom);
							else
								$rep = date('H:i', $evt->tsfrom);
						}
					} else {
						$rep = '';	
					}
					break;
				case 'endtime':
					if (!empty($evt->tsto)) {
						if (isset($opts['fmt']))
							$rep = date($opts['fmt'], $evt->tsto);
						else
							$rep = date('H:i', $evt->tsto);
					} else {
						$rep = '';	
					}
					break;
				case 'duration':
					$t = $opts['type'];
					$a = $opts['suffix'];
					$e = $opts['empty'];
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
				case 'publishdate':
					if (!empty($evt->publishdate)) {
						if (isset($opts['fmt']))
							$rep = date($opts['fmt'], $evt->publishdate);
						else
							$rep = date('d.m.Y', $evt->publishdate);
					} else {
						$rep = '';	
					}
					break;
				case 'publishtime':
					if (!empty($evt->publishdate)) {
						if (isset($opts['fmt']))
							$rep = date($opts['fmt'], $evt->publishdate);
						else
							$rep = date('H:i', $evt->publishdate);
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
					if (!empty($page_url)) {
						$rep = $page_url.$evt->eventid;
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
				
			}
			$content = preg_replace('/'.preg_quote($m[0]).'/', $rep, $content, 1);
		}
		
		return $content;
	}
	
	/**
	 * Prints or returns a list of events (for external use)
	 * @param $args @see getEventsExternal
	 * @return unknown_type
	 */
	function printEvents($args) {
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
	function printEventsList($args) {
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
		unset($filter['orderby']);
		$filter['orderby'] = array('tsfrom');
		
		$dir = $filter['orderdir'];
		if (isset($dir[0]))
			$dir = $dir[0];
		else
			$dir = 'asc';
		unset($filter['orderdir']);
		$filter['orderdir'] = array($dir);
		
		$ret = '';
		$evt = $this->getEventsExternal($args);
		
		if ($evt !== false) {
			$d = $m = $y = -1;
			foreach($evt as $e) {
				$dn = $e->getStart('d');
				$mn = $e->getStart('m');
				$yn = $e->getStart('y');
				if ($dn != $d || $mn != $m || $yn != $y) {
					if ($d >= 0) {
						$ret .= '</ul></li>';
					}
					$ret .= '<li class="event_header">'.$e->getStart($groupby_hdr).'<ul class="events">';
					$d = $dn;
					$m = $md;
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
			
			if (!isset($filter['dateto'])) {
				if ($filter['datemode'] == FSE_DATE_MODE_START) {
					$where .= ' (e.tsfrom >= '.$filter['datefrom'].')';	
				} else {
					$where .= ' (e.tsto >= '.$filter['datefrom'].')';	
				}	
			} elseif (!isset($filter['datefrom'])) {
				if ($filter['datemode'] == FSE_DATE_MODE_END) {
					$where .= ' (e.tsto <= '.$filter['dateto'].')';	
				} else {
					$where .= ' (e.tsfrom <= '.$filter['dateto'].')';	
				}
			} else {
				if ($filter['datemode'] == FSE_DATE_MODE_ALL) {
					$where .= ' ((e.tsfrom >= '.$filter['datefrom'].' AND e.tsfrom <= '.$filter['dateto'].') OR '.
					  ' (e.tsto   >= '.$filter['datefrom'].' AND e.tsto   <= '.$filter['dateto'].') OR '.
					  ' (e.tsfrom < '.$filter['datefrom'].' AND e.tsto > '.$filter['dateto'].'))';
				} elseif ($filter['datemode'] == FSE_DATE_MODE_START) {
					$where .= ' (e.tsfrom >= '.$filter['datefrom'].' AND e.tsfrom <= '.$filter['dateto'].')';
				} else {
					$where .= ' (e.tsto >= '.$filter['datefrom'].' AND e.tsto <= '.$filter['dateto'].')';
				}
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
		$datemode = FSE_DATE_MODE_START;
		$d = time();
		$state = 'publish';
		$datefrom = mktime(0,0,0, date('m', $d), date('d', $d), date('Y', $d));
		$categories = $orderby = $orderdir = $include = $exclude = array();
		
		// Get some values from options
		$number = intval(get_option('fse_number'));
		
		foreach($args as $k => $a) {
			switch($k) {
				case 'number':
					$a = intval($a);
					if (!empty($a))
						$number = $a;
				case 'exclude':
					if (!is_array($a))
						$a = array();
					foreach($a as $e) {
						$e = intval($e);
						if (!empty($e)) {
							$exclude[] = intval($e);
						}
					}
					break;
				case 'include':
					if (!is_array($a))
						$a = array();
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
						$a = array($a);
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
			if ($et->eventid > 0)
				$ret[] = $et;
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
	 * Returns the page start html code
	 * @param $title Postbox Title
	 * @return String Page start html
	 */
	function pageStart($title, $message = '', $icon = '') {
		if (empty($icon)) {
			$icon = 'icon-options-general';	
		}
		$ret =  '<div class="wrap">
				<div id="'.$icon.'" class="icon32"><br /></div>
				<div id="otc"><h2>'.$title.'</h2>';
		if (!empty($message)) 
			$ret .= '<div id="message" class="updated fade"><p><strong>'.$message.'</strong></p></div>';
		$ret .= '</div>';
		return $ret;
	}
	
	/**
	 * Returns the page end html code
	 * @return String Page end html
	 */
	function pageEnd() {
		return '</div>';
	}
	
	/**
	 * Returns the code for a widget container
	 * @param $width Width of Container (percent)
	 * @return String Container start html
	 */
	function pagePostContainerStart($width) {
		return '<div class="postbox-container" style="width:'.$width.'%;">
					<div class="metabox-holder">	
						<div class="meta-box-sortables">';
	}
	
	/**
	 * Returns the code for the end of a widget container
	 * @return String Container end html
	 */
	function pagePostContainerEnd() {
		return '</div></div></div>';
	}
	
	/**
	 * Returns the code for the start of a postbox
	 * @param $id Unique Id
	 * @param $title Title of pagebox
	 * @return String Postbox start html
	 */
	function pagePostBoxStart($id, $title) {
		return '<div id="'.$id.'" class="postbox">
			<h3 class="hndle"><span>'.$title.'</span></h3>
			<div class="inside">';
	}
	
	/**
	 * Returns the code for the end of a postbox
	 * @return String Postbox end html
	 */
	function pagePostBoxEnd() {
		return '</div></div>';
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
		
		$sql = "CREATE TABLE `".$wp->prefix.'fsevents'."` (
			`eventid` INT NOT NULL AUTO_INCREMENT  ,
			`subject` VARCHAR( 255 ) NOT NULL ,
			`tsfrom` INT NOT NULL ,
			`tsto` INT NOT NULL ,
			`allday` TINYINT( 1 ) NOT NULL DEFAULT '0',
			`description` TEXT NULL ,
			`location` VARCHAR( 255 ) NULL ,
			`author` BIGINT NOT NULL
			`createdate` INT NOT NULL
			`publishauthor` BIGINT NOT NULL
			`publishdate` INT NOT NULL
			`state` VARCHAR( 10 ) NOT NULL,
			PRIMARY KEY  (`eventid`)
			);";
		
		dbDelta($sql);
		
		$sql = "CREATE TABLE `".$wp->prefix."fsevents_cats` (
			`eventid` BIGINT NOT NULL,
			`catid` BIGINT NOT NULL,
			PRIMARY KEY  (`eventid`, `catid`)
			);";
		
		dbDelta($sql);
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
class fsEvent {
	var $eventid = 0;
	var $subject;
	var $location; 
	var $description;
	var $tsfrom;
	var $tsto;
	var $allday;
	var $author;
	var $createdate;
	var $publishauthor;
	var $publishdate;
	var $categories = array();
	var $state;
	
	// For Admin only
	var $date_admin_from;
	var $date_admin_to;
	var $time_admin_from;
	var $tim_admin_to;
	
	// Formated values
	var $author_t;
	var $publishauthor_t;
	var $categories_t = array();
	
	// Options
	var $date_format;
	var $time_format;
	var $date_time_format;
	var $date_admin_format;
	var $time_admin_format;
	
	function fsEvent($eventid, $state = '', $admin_fields = true) {
		global $wpdb;
		
		$this->loadOptions($admin_fields);
			
		$this->eventid = intval($eventid);
		
		if (empty($this->eventid)) {
			return;
		}
		
		if (!empty($state))
			$sql = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'fsevents '.' WHERE eventid='.$this->eventid.' AND state=%s', $state);
		else
			$sql = 'SELECT * FROM '.$wpdb->prefix.'fsevents '.' WHERE eventid='.$this->eventid;
		
		$ret = $wpdb->get_row($sql, OBJECT);
		
		if ($ret == NULL) {
			$evt->eventid = 0;
			return;
		}
	
		
		$this->subject = $ret->subject;
		$this->location = $ret->location;
		$this->description = $ret->description;
		$this->tsfrom = $ret->tsfrom;
		$this->tsto = $ret->tsto;
		$this->allday = $ret->allday;
		$this->author = $ret->author;
		$this->publishauthor = $ret->publishauthor;
		$this->createdate = $ret->createdate;
		$this->publishdate = $ret->publishdate;
		$this->state = $ret->state;
		
		$this->categories = $wpdb->get_col('SELECT catid FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$this->eventid);
		
		if (is_array($this->categories)) {
			
			// Get Cats description and move id to key
			$cats = get_categories(array('hide_empty'=>false));
			foreach($cats as $c) {
				$ca[$c->cat_ID] = $c->name;	
			}
			unset($cats);
			foreach($this->categories as $c) {
				if (isset($ca[$c])) {
					$this->categories_t[$c] = $ca[$c];
				}
			}
		} else {
			$this->categories = array();
			$this->categories_t = array();
		}
		
		// Get Usernames
		$u = new WP_User($this->author);
		if (isset($u->display_name))
			$this->author_t = $u->display_name;
		unset($u);
		
		if (!empty($this->publishauthor)) {
			$u = new WP_User($this->publishauthor);
			if (isset($u->display_name))
				$this->publishauthor_t = $u->display_name;
			unset($u);
		}
		
		if ($admin_fields) {
			$this->date_admin_from = date($this->date_admin_format, $this->tsfrom);
			$this->date_admin_to   = date($this->date_admin_format, $this->tsto);
			$this->time_admin_from = date($this->time_admin_format, $this->tsfrom);
			$this->time_admin_to   = date($this->time_admin_format, $this->tsto);
		}
	}	

	function loadOptions($admin_fields = true) {
		// Load options
		if (get_option('fse_df_wp') == 1)
			$this->date_format = get_option('date_format');
		else
			$this->date_format = get_option('fse_df');
		
		if (get_option('fse_tf_wp') == 1)
			$this->time_format = get_option('time_format');
		else
			$this->time_format = get_option('fse_tf');
		
		$this->date_time_format = $this->date_format.' '.$this->time_format;
		
		// Format dates for admin
		if ($admin_fields == true) {
			$fmt = get_option('fse_df_admin');
			$sep = get_option('fse_df_admin_sep');
			$admfmt = '';
			for ($i=0; $i<strlen($fmt); $i++) {
				if ($i > 0)
					$admfmt .= $sep;
				$admfmt .= $fmt[$i];
			}
			
			$this->date_admin_format = $admfmt;
			$this->time_admin_format = 'H:i';
		}
	}
	
	/**
	 * Returns the formatted start date/time string
	 * @param $fmt Format (See PHP function date())
	 * @param $mode Mode (1=Date+Time, 2=Date only, 3=Time only)
	 * @return String Formatted date string
	 */
	function getStart($fmt = '', $mode = 1) {
		if (empty($this->tsfrom))
			return '';
			
		if (empty($fmt)) {
			switch($mode) {
				case 1:
					$fmt = $this->date_format.' '.$this->time_format;
					break;
				case 2:
					$fmt = $this->date_format;
					break;
				case 3:
					$fmt = $this->time_format;
					break;
			}
		}
		
		return date($fmt, $this->tsfrom);
	}
	
	/**
	 * Returns the formatted end date/time string
	 * @param $fmt Format (See PHP function date())
	 * @param $mode Mode (1=Date+Time, 2=Date only, 3=Time only)
	 * @return String Formatted date string
	 */
	function getEnd($fmt = '', $mode = 1) {
		if (empty($this->tsto))
			return '';
			
		if (empty($fmt)) {
			switch($mode) {
				case 1:
					$fmt = $this->date_format.' '.$this->time_format;
					break;
				case 2:
					$fmt = $this->date_format;
					break;
				case 3:
					$fmt = $this->time_format;
					break;
			}
		}
		
		return date($fmt, $this->tsto);
	}
	
	function userCanPublishEvent() {
		
		if (empty($this->eventid))
			return true;
		
		$ret = $this->userCanEditEvent($e);
		if ($ret == false)
			return false;
		else
			return current_user_can(2);	
	}
	
	function userCanViewEvent() {
		return current_user_can(0);
	}
	
	/**
	 * Check if the user can edit an event
	 * If the user is contributor (level=1+): Only own events in draft state
	 * If the user is author (level=2+): Only own events
	 * If the user is editor+ (level=7+)
	 * @param $e Event object
	 * @return True, if use can edit an event
	 */
	function userCanEditEvent() {
		global $user_ID;
		
		if (empty($this->eventid))
			return true;
		
		if ($this->author != $user_ID) {
			return current_user_can(7);	
		// Edit of published only by editor!
		} elseif ($this->state == 'publish') {
			return current_user_can(7);
		} else {
			return current_user_can(1);	
		}
	}
	
	/**
	 * Check if the user can delete an event
	 * If the user is contributor (level=1+): Only own events in draft state
	 * If the user is author (level=2+): Only own events
	 * If the user is editor+ (level=7+)
	 * @param $e Event object
	 * @return True, if use can delete an event
	 */
	function userCanDeleteEvent() {
		global $user_ID;
		
		if (empty($this->eventid))
			return true;
		
		if ($this->author != $user_ID) {
			return current_user_can(7);	
		} elseif ($this->state == 'publish') {
			return current_user_can(2);
		} else {
			return current_user_can(1);	
		}
	}
	
}

/**
 * Returns a single events as an object
 * @param $eventid Event Id
 * @return Object of fsEvent
 */
function fse_get_event($eventid) {
	$e = new fsEvent($eventid, '', false);
	if ($e->eventid == 0)
		return false;
	else
		return $e;
}

function fse_print_events($args) {
	global $fsCalendar;
	
	return $fsCalendar->printEvents($args);
}

function fse_print_events_list($args) {
	global $fsCalendar;
	
	return $fsCalendar->printEventsList($args);
}

function fse_get_events($args = array()) {
	global $fsCalendar;
	
	return $fsCalendar->getEventsExternal($args);
}
?>