<?php
class fsCalendarAdmin {
	
	var $settings;
	
	function fsCalendarAdmin() {
		add_action('admin_menu',           array(&$this, 'hookAddAdminMenu'), 98);
		add_action('admin_menu',           array(&$this, 'hookOrderAdminMenu'), 99);
		
		add_action('admin_init',           array(&$this, 'hookRegisterScriptsAdmin'));
		add_action('admin_init',           array(&$this, 'hookRegisterStylesAdmin'));
		
		add_filter('plugin_action_links',  array(&$this, 'hookAddPlugInSettingsLink'), 10, 2 );
		
		$this->settings = new fsCalendarSettings();
	}
	
	/**
	 * Creates a menu entry in the settings menu
	 * @return void
	 */
	function hookAddAdminMenu() {
		add_menu_page(   __('Edit events', fsCalendar::$plugin_textdom),
						 __('Calendar', fsCalendar::$plugin_textdom), 
						 'edit_posts', 
						 fsCalendar::$plugin_filename, 
						 array(&$this, 'createCalendarPage')); 
		add_submenu_page(fsCalendar::$plugin_filename, 
						 __('Edit events', fsCalendar::$plugin_textdom), 
						 __('Edit', fsCalendar::$plugin_textdom), 
						 'edit_posts', 
						 fsCalendar::$plugin_filename, 
						 array(&$this, 'createCalendarPage'));
		add_submenu_page(fsCalendar::$plugin_filename,
						 __('Add new event', fsCalendar::$plugin_textdom), 
						 __('Add new', fsCalendar::$plugin_textdom), 
						 'edit_posts', 
						 'wp-cal-add', 
						 array(&$this, 'createCalendarAddPage'));		
	}
	
	/**
	 * Changes the position of the created menu
	 * @return void
	 */
	function hookOrderAdminMenu() {
		global $menu;

		foreach($menu as $k => $m) {
			if ($m['2'] == fsCalendar::$plugin_filename) {
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
		wp_enqueue_script('common');
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_script('fs-datepicker', fsCalendar::$plugin_js_url.'ui.datepicker.js');
		wp_enqueue_script('fs-date', fsCalendar::$plugin_js_url.'date.js');
		wp_enqueue_script(fsCalendar::$plugin_id, fsCalendar::$plugin_js_url.'helper.js');
				
		if ((strpos($_SERVER['QUERY_STRING'], fsCalendar::$plugin_filename) !== false && 
		   (isset($_GET['action']) && ($_GET['action'] == 'new' || $_GET['action'] == 'edit') || $_GET['action'] == 'copy')) ||
		   (isset($_GET['page']) && $_GET['page'] == 'wp-cal-add')) {
			wp_enqueue_script('post');
			if (user_can_richedit()) {
				wp_enqueue_script('editor');
				wp_enqueue_script('editor-functions');
				add_thickbox();
				wp_enqueue_script('media-upload');
				wp_enqueue_script('post');
				wp_enqueue_script('tiny_mce');
				
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
		wp_enqueue_style('fs-styles-dp', fsCalendar::$plugin_css_url.'jquery-ui-1.7.2.custom.css');
		wp_enqueue_style('fs-styles', fsCalendar::$plugin_css_url.'default.css');
		wp_enqueue_style('wp-calendar', fsCalendar::$plugin_css_url.'wpcalendar.css');
		
		/*if (strpos($_SERVER['QUERY_STRING'], fsCalendar::$plugin_filename) !== false && 
		   ($_GET['action'] == 'new' || $_GET['action'] == 'edit')) {
			wp_enqueue_style('post');
		}*/
	}
	
	/**
	 * Adds a "Settings" link for this plug-in in the plug-in overview
	 * @TODO Migrate to WP 3.0
	 * @return void
	 */
	function hookAddPlugInSettingsLink($links, $file) {
		if ($file == fsCalendar::$plugin_filename) {
			array_unshift($links, '<a href="options-general.php?page='.$file.'&amp;action=settings">'.__('Settings', fsCalendar::$plugin_textdom).'</a>');
		}
		return $links;
	}
	
	function createCalendarPage() {
		global $wpdb;
		global $user_ID;
		global $fsCalendar;
		
		if (isset($_GET['action'])) {
			if ($_GET['action'] == 'edit') {
				$this->createCalendarEditPage();
				return;
			} elseif ($_GET['action'] == 'view' ) {
				$this->createCalendarEditPage();
				return;
			} elseif ($_GET['action'] == 'new' || $_GET['action'] == 'copy') {
				$this->createCalendarAddPage();
				return;
			}
		}
		
		$evt->eventid = 0;
		include('FormOverview.php');
	}
	
	function createCalendarEditPage() {
		global $wpdb;
		global $user_ID;
		global $fsCalendar;
		
		$evt->eventid = intval($_GET['event']);
		include('FormEvent.php');
	}
	
	function createCalendarAddPage() {
		global $wpdb;
		global $user_ID;
		global $fsCalendar;
		
		$evt->eventid = 0;
		include('FormEvent.php');
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
			echo '<ul>';
			foreach($selected_cats as $c) {
				echo '<li>'.$ca[$c].'</li>';
			}
			echo '</ul>';
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
	function printNavigationBar($filter = array(), $part = 1, $page = 1, $epp = 20, $count = 0, $bl = '') {
		global $wpdb;
		global $fsCalendar;
		?>
		<div class="tablenav">
			<div class="alignleft actions">
				<select name="action<?php echo ($part == 2 ? '2' : ''); ?>">
					<option selected="selected" value=""><?php _e('Choose action', fsCalendar::$plugin_textdom); ?></option>
					<option value="delete"><?php _e('Delete', fsCalendar::$plugin_textdom); ?></option>
					<?php 
					if ($fsCalendar->userCanPublishEvents()) {
						echo '<option value="publish">'.__('Publish', fsCalendar::$plugin_textdom).'</option>';
					}
					?>
					<?php 
					if ($fsCalendar->userCanEditEvents()) {
						echo '<option value="draft">'.__('Set to Draft', fsCalendar::$plugin_textdom).'</option>';
					}
					?>
				</select>
				<input id="doaction<?php echo $part; ?>" class="button-secondary action" type="submit" name="doaction" value="<?php _e('Apply', fsCalendar::$plugin_textdom); ?>" />
				<?php if ($part == 1) {?>
					<select name="event_start">
					<option value="-1"<?php echo (!isset($filter['datefrom']) ? ' selected="selected"' : ''); ?>><?php _e('Show all dates', fsCalendar::$plugin_textdom); ?></option>
					<option value="0"<?php echo (isset($filter['datefrom']) && !isset($filter['dateto']) ? ' selected="selected"' : ''); ?>><?php _e('Show future dates only', fsCalendar::$plugin_textdom); ?></option>
					<?php 
					$min = $wpdb->get_var('SELECT MIN(tsfrom) AS min FROM '.$wpdb->prefix.'fsevents');
					$max = $wpdb->get_var('SELECT MAX(tsto)   AS max FROM '.$wpdb->prefix.'fsevents');
					if ($min != NULL && $max != NULL) {
						$ms = fsCalendar::date('m', $min);
						$ys = fsCalendar::date('Y', $min);
						$me = fsCalendar::date('m', $max);
						$ye = fsCalendar::date('Y', $max);
						
						while($ys <= $ye) {
							while($ms<=12 && ($ys < $ye || $ms <= $me)) {
								$time = mktime(0, 0, 0, $ms, 1, $ys);
								echo '<option value="'.$time.'"'.($time == $filter['datefrom'] ? ' selected="selected"' : '').'>'.fsCalendar::date_i18n('F Y', $time).'</option>';
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
					<input id="event-query-submit" class="button-secondary" type="submit" value="<?php _e('Filter', fsCalendar::$plugin_textdom);?>" />
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
}
?>