<?php
class fsCalendarAdmin {
	
	var $settings;
	
	function fsCalendarAdmin() {
		add_action('admin_menu',           array(&$this, 'hookAddAdminMenu'), 98);
		add_action('admin_menu',           array(&$this, 'hookOrderAdminMenu'), 99);
		
		add_action('admin_init',           array(&$this, 'hookRegisterScriptsAdmin'));
		add_action('admin_init',           array(&$this, 'hookRegisterStylesAdmin'));
		
		add_action('add_meta_boxes', 	   array(&$this, 'hookAddPostCalendarBox'));
		add_action('save_post', 		   array(&$this, 'hookSaveEventFromPost'));
		add_action('admin_notices', 	   array(&$this, 'hookSendDBUpdateNotice'));
		
		add_action('wp_ajax_fs_subject_ac_cb', array(&$this, 'hockAjaxSubjectCallback'));
		add_action('wp_ajax_fs_location_ac_cb', array(&$this, 'hockAjaxLocationCallback'));
		
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
		$editor = $datepicker = $tabs = false;
		
		if (strpos($_SERVER['REQUEST_URI'], 'wp-cal-add') > 0) {
			$datepicker = true;
			$editor = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], fsCalendar::$plugin_filename) > 0 &&
			isset($_GET['action'])) {
			$datepicker = true;
			$editor = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], 'post.php') > 0 ||
				  strpos($_SERVER['REQUEST_URI'], 'post-new.php') > 0) {
			$datepicker = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], 'wp-cal-settings') > 0) {
			$tabs = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], 'fsCalendar.php') > 0) {
			
		} else {
			return;
		}
		
		wp_enqueue_script('common');
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-autocomplete');
		
		wp_enqueue_script(fsCalendar::$plugin_id, fsCalendar::$plugin_js_url.'helper.js');
		
		if ($datepicker) {
			wp_enqueue_script('fs-datepicker', fsCalendar::$plugin_js_url.'ui.datepicker.js');
		}
		
		if ($tabs) {
			wp_enqueue_script('jquery-ui-widget');
			wp_enqueue_script('jquery-ui-tabs');
		}

		if ($editor) {
			if (!isset($_GET['action']) || $_GET['action'] != 'view') {
				wp_enqueue_script('post');
				if (user_can_richedit() && !function_exists('wp_editor')) {
					wp_enqueue_script('editor');
					wp_enqueue_script('word-count');
					add_thickbox();
					wp_enqueue_script('media-upload');
					
					add_action( 'admin_print_footer_scripts', 'wp_tiny_mce', 25 );
					
					if (function_exists('wp_preload_dialogs')) { // WP 3.2
						add_action( 'admin_print_footer_scripts', 'wp_preload_dialogs', 30 );
					} else {
						add_action( 'admin_print_footer_scripts', 'wp_preload_dialogs', 30 );
					}
					//wp_enqueue_script('quicktags');
				}
			}
		}
	}

	/**
	 * Loads all necessary stylesheets for the admin interface
	 * @return void
	 */
	function hookRegisterStylesAdmin() {
		$editor = $datepicker = $tabs = false;
		
		if (strpos($_SERVER['REQUEST_URI'], 'wp-cal-add') > 0) {
			$datepicker = true;
			$editor = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], fsCalendar::$plugin_filename) > 0 &&
			isset($_GET['action'])) {
			$datepicker = true;
			$editor = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], 'post.php') > 0 ||
		          strpos($_SERVER['REQUEST_URI'], 'post-new.php') > 0) {
			$datepicker = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], 'wp-cal-settings') > 0) {
			$tabs = true;
		} elseif (strpos($_SERVER['REQUEST_URI'], 'post_type=page') > 0) {
			
		} else {
			return;
		}
		
		wp_enqueue_style('fs-styles', fsCalendar::$plugin_css_url.'default.css');
		wp_enqueue_style('wp-calendar', fsCalendar::$plugin_css_url.'wpcalendar.css');
		
		if ($editor) {		
			wp_enqueue_style('dashboard');
			wp_enqueue_style('thickbox');
		}
		
		if ($datepicker) {
			wp_enqueue_style('wp-cale-ui-dp', fsCalendar::$plugin_css_url.'ui.datepicker.css');
		}
		
		if ($tabs) {
			//wp_enqueue_style('colors');
			wp_enqueue_style('wp-tab-ui', fsCalendar::$plugin_css_url.'ui.tab.css');
		}
	}
	
	/**
	 * Adds a "Settings" link for this plug-in in the plug-in overview
	 * @TODO Migrate to WP 3.0
	 * @return void
	 */
	function hookAddPlugInSettingsLink($links, $file) {
		if ($file == fsCalendar::$plugin_filename) {
			array_unshift($links, '<a href="options-general.php?page=wp-cal-settings">'.__('Settings', fsCalendar::$plugin_textdom).'</a>');
		}
		return $links;
	}
	
	/**
	 * Adds a "WP Calendar" Box to the Post 
	 * @return unknown_type
	 */
	function hookAddPostCalendarBox() {
		add_meta_box('wpcalevent', 
					 __('Calendar Event', fsCalendar::$plugin_textdom),
					 array(&$this, 'createCalendarPostEventBox'),
					 'post',
					 'side');
	}
	
	function hookSaveEventFromPost($post_id) {
		global $fsCalendar;
		
		@session_start();
		
		// Check autosave
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
    		return;
    	
    	// Store the postdata in the session, because all data gets lost on the
    	// frontend, when a error occurs!
    	$_SESSION['fse_postdata'] = $_POST;
    		
    	// Check nonce
		if (isset($_POST['eventid']) && $action != 'view') {
			$nonce = $_POST['_fseevent'];
			if (!wp_verify_nonce($nonce, 'event')) {
				$_SESSION['fse_error'] = __('Security check failed', fsCalendar::$plugin_textdom);
				return;
			}
		}
		
		$active   = (isset($_POST['fseventactive']) && $_POST['fseventactive'] == true);
		$sync     = (isset($_POST['fseventsync']) && $_POST['fseventsync'] == true);
		$synconce = (isset($_POST['fseventupdate']) && $_POST['fseventupdate'] == true);
		
		// WP Calender event active?
		if (!$active)
			return;
			
		// Only use the main post (nut the revisions)
		if (wp_is_post_revision($post_id) !== false) {
			return;
		}
		
		// Check if an event exists
		$evt = new fsEvent(0, '', true, $post_id);
		
		// Neuer Event
		if (empty($evt->eventid)) {
			if (!$fsCalendar->userCanAddEvents()) {
				$_SESSION['fse_error'] = __('You do not have the permission to create events', fsCalendar::$plugin_textdom);
				return;
			}
		} else {
			// Check if needs to be updated!
			//if (!$sync && !$synconce)
			//	return;
				
			// Check authority to edit
			if (!$evt->userCanEditEvent()) {
				$_SESSION['fse_error'] = __('No permission to edit event', fsCalendar::$plugin_textdom);
				return;
			}
		}
		
		// Now add all the data
		$evt->postid = $post_id;
		$evt->updatedbypost = $sync;
		
		// Some date are only updated when requestet
		if ($sync || $synconce) {
			$post = get_post($post_id);
			$cats = wp_get_post_categories($post_id);
			
			$evt->subject = $post->post_title;
			$evt->description = $post->post_content;
			$evt->categories = array();
			foreach($cats as $c) {
				$evt->categories[] = $c;
			}
		}
		
		$evt->allday     		= (isset($_POST['event_allday']) ? true : false);
		$evt->date_admin_from   = $_POST['event_from'];
		$evt->date_admin_to     = $_POST['event_to'];
		$evt->time_admin_from   = $_POST['event_tfrom'];
		$evt->time_admin_to     = $_POST['event_tto'];
		$evt->location    		= $_POST['event_location'];
		
		if (($ret = $evt->saveToDataBase()) === true) {
			$_SESSION['fse_success'] = 'Successfully saved';
		} else {
			$_SESSION['fse_error'] = $ret;	
		}
		
		return $post_id;
	}
	
	function hockAjaxSubjectCallback() {
		global $wpdb; 
		
		$term = $_GET['term'].'%';
		$sql = $wpdb->prepare('SELECT DISTINCT subject FROM '.$wpdb->prefix.'fsevents '.' WHERE subject LIKE %s', $term);
		
		$res = $wpdb->get_col($sql);
		
		echo json_encode($res);
		
		die();
	}
	
	function hockAjaxLocationCallback() {
		global $wpdb; 
		
		$term = $_GET['term'].'%';
		$sql = $wpdb->prepare('SELECT DISTINCT location FROM '.$wpdb->prefix.'fsevents '.' WHERE location LIKE %s', $term);
		
		$res = $wpdb->get_col($sql);
		
		echo json_encode($res);
		
		die();
	}
	
	function hookSendDBUpdateNotice() {
		$dbver = get_option('fse_db_version', -1);
		if ($dbver < FSE_DB_VERSION) {
			echo "<div class='updated'><p>".__('The database of <i>WP Calendar</i> has changed. Please <a href="'.get_admin_url().'plugins.php?s=wp%20calendar">reactivate</a> (deactivate and activate) the plugin to upgrade your database structure.')."</p>".
			'<p>It is <b>highly</b> recommended to make a full backup of your database tables! The developer is not responsible for any data loss!</p>'."</div>";
		}
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
	
	function createCalendarPostEventBox() {
		global $post;
		global $fsCalendar;
		
		$post_id = $post->ID;
		if ($res = wp_is_post_revision($post_id))
			$post_id = $res;
			
		$evt = new fsEvent(0, '', true, $post_id);
		
		//print_r($_SESSION['fse_postdata']);
		
		if (isset($_SESSION['fse_postdata']['event_location'])) {
			$evt->date_admin_from   = $_SESSION['fse_postdata']['event_from'];
			$evt->date_admin_to     = $_SESSION['fse_postdata']['event_to'];
			$evt->time_admin_from   = $_SESSION['fse_postdata']['event_tfrom'];
			$evt->time_admin_to     = $_SESSION['fse_postdata']['event_tto'];
			$evt->location    		= $_SESSION['fse_postdata']['event_location'];
			$evt->updatedbypost     = (isset($_SESSION['fse_postdata']['fseventsync']) && $_SESSION['fse_postdata']['fseventsync'] == true);
		}
		
		$active   = (isset($_SESSION['fse_postdata']['fseventactive']) && $_SESSION['fse_postdata']['fseventactive'] == true);
		$sync     = (isset($_SESSION['fse_postdata']['fseventsync']) && $_SESSION['fse_postdata']['fseventsync'] == true);
		
		if (isset($_SESSION)) {
			unset($_SESSION['fse_postdata']);
		}
		
		// Create event?
		if (empty($evt->eventid)) {
			
			if (!$fsCalendar->userCanAddEvents()) {
				echo '<p>'.__('You do not have the permission to create events', fsCalendar::$plugin_textdom).'.</p>';
				return;
			}
			
			echo '<p><input type="checkbox" value="1" id="fseventactive" name="fseventactive" 
					onclick="fse_togglePostEvent(this.checked);"'.($active ? ' checked="checked"' : '').' /> ';
			echo '<label for="fseventactive">'.__('Create WP Calendar event', fsCalendar::$plugin_textdom).'</label></p>';
			?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				fse_togglePostEvent(jQuery('#fseventactive').attr('checked'));
			});
			</script>
			<?php 
		} else {
			
			if (!$fsCalendar->userCanEditEvents()) {
				echo '<p>'.__('This post is linked to a calendar event, but you do not have the permission to edit this event', fsCalendar::$plugin_textdom).'.</p>';
				return;
			}
			
			echo '<p>'.__('This post is linked to a calendar event. To jump to your calender event click '.
			'<a href="admin.php?page='.fsCalendar::$plugin_filename.'&amp;action=edit&amp;event='.esc_attr($evt->eventid).'">here</a>', fsCalendar::$plugin_textdom).'.</p>';
			if ($evt->updatedbypost) {
				echo '<p>'.__('The events subject, description and categories are automatically updated when the post is saved', fsCalendar::$plugin_filename).'.<br />'.
					__('You can turn this feature off by unchecking the &laquo;Keep updated&raquo; checkbox', fsCalendar::$plugin_filename).'.</p>';
			} else {
				echo '<p>'.__('The events subject, description and categories are <strong>not</strong> automatically updated when the post is saved', fsCalendar::$plugin_textdom).'.</p>'.
					 '<p>'.__('Please check the &laquo;Update at next save&raquo; checkbox for updating these values at next save '.
						'or turn it on by default for this post/event by checking the &laquo;Keep updated&raquo; checkbox', fsCalendar::$plugin_textdom).'.</p>';
				echo '<p><input type="checkbox" value="1" name="fseventupdate" id="fseventupdate"'.($synconce ? ' checked="checked"' : '').' /> <label for="fseventupdate">'.__('Update at next save', fsCalendar::$plugin_textdom).'</label></p>';
			}
			echo '<input type="hidden" name="fseventactive" value="1" />';
		}
		
		wp_nonce_field('event', '_fseevent');
		
		echo '<div id="fseventdata">';	
		echo '<p><input type="checkbox" value="1" name="fseventsync" id="fseventsync" '.($evt->updatedbypost == true ? ' checked="checked"' : '').'/> <label for="fseventsync">'.__('Keep updated', fsCalendar::$plugin_textdom).'</label></p>';	
		echo '<p>'.__('Location', fsCalendar::$plugin_textdom).'<br />'.
			'<input type="text" name="event_location" style="width: 98%;" value="'.esc_attr($evt->location).'" /></p>';
		echo '<div style="padding: 0px 6px;">';
		echo $this->postBoxDateAndTime($evt);
		echo '</div>';
		echo '</div>';
		
		?>
		<div id="minor-publishing">
			<div id="misc-publishing-actions">
				<div class="misc-pub-section">
					<?php _e('State', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display">
					<?php echo fsCalendar::$valid_states[$evt->state]; ?></span>
					<?php if ($evt->state ==  'publish' && $evt->userCanEditEvent()) { ?>
					<a class="hide-if-no-js" href="" onClick="document.forms['event'].jsaction.value='draft'; document.forms['event'].submit(); return false;"><?php _e('Change to Draft', fsCalendar::$plugin_textdom)?></a>
					<?php } ?>
				</div>
				<div class="misc-pub-section">
					<?php _e('Published by', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (empty($evt->publishauthor_t) ? '-' : esc_attr($evt->publishauthor_t)); ?></span>
				</div>
				<div class="misc-pub-section">
					<?php _e('Published', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->publishdate) ? mysql2date($evt->date_time_format, $evt->publishdate) : '-'); ?></span>
				</div>
			</div>
			<div class="clear"/></div>
		</div>
		<?php 
		
		//echo '</div>';
	}
	
	/**
	 * Creates the Postbox for category selection
	 * @param $selected_cats
	 * @param $view
	 * @return unknown_type
	 */	
	function postBoxCategories($selected_cats, $view = false) {
		if ($view == false) {
			$defaults = array('taxonomy' => 'category');
			$args = array();
			/*if ( !isset($box['args']) || !is_array($box['args']) )
				$args = array();
			else
				$args = $box['args'];*/
			extract( wp_parse_args($args, $defaults), EXTR_SKIP );
			$tax = get_taxonomy($taxonomy);
		?>
		
			<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
				<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
					<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $tax->labels->all_items; ?></a></li>
					<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
				</ul>
		
				<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
					<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
						<?php $popular_ids = wp_popular_terms_checklist($taxonomy); ?>
					</ul>
				</div>
		
				<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
					<?php
		            $name = ( $taxonomy == 'category' ) ? 'post_category' : 'tax_input[' . $taxonomy . ']';
		            echo "<input type='hidden' name='{$name}[]' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
		            ?>
					<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
						<?php wp_terms_checklist(0, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids, 'selected_cats' => $selected_cats ) ) ?>
					</ul>
				</div>
			<?php if ( !current_user_can($tax->cap->assign_terms) ) : ?>
			<p><em><?php _e('You cannot modify this taxonomy.'); ?></em></p>
			<?php endif; ?>
			<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
					<div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
						<h4>
							<a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js" tabindex="3">
								<?php
									/* translators: %s: add new taxonomy label */
									printf( __( '+ %s' ), $tax->labels->add_new_item );
								?>
							</a>
						</h4>
						<p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
							<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $tax->labels->add_new_item; ?></label>
							<input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
							<label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
								<?php echo $tax->labels->parent_item_colon; ?>
							</label>
							<?php wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
							<input type="button" id="<?php echo $taxonomy; ?>-add-submit" class="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
							<?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy, false ); ?>
							<span id="<?php echo $taxonomy; ?>-ajax-response"></span>
						</p>
					</div>
				<?php endif; ?>
			</div>
			<?php
		} else {
			$cats = get_categories(array('hide_empty'=>false));
			foreach($cats as $c) {
				$ca[$c->cat_ID] = $c->name;
			}
			$first = true;
			echo '<ul>';
			foreach($selected_cats as $c) {
				echo '<li>'.$ca[$c].'<input type="hidden" name="post_category[]" value="'.$c.'" /></li>';
			}
			echo '</ul>';
		}
	}
	
	function postBoxDateAndTime($evt, $view = false) {
		$df = get_option('fse_df_admin');
		$ds = get_option('fse_df_admin_sep');
		
		$gc_enabled = get_option('fse_adm_gc_enabled');
		$gc_mode = get_option('fse_adm_gc_mode');
		
		$f = $evt->date_admin_format;
		$f = str_replace('d', 'dd', $f);
		$f = str_replace('m', 'mm', $f);
		$f = str_replace('Y', 'yy', $f);
		?>
		<table class="fs-table">
			<tbody>
				<tr>
					<th scope="row" style="vertical-align: middle;"><?php _e('From', fsCalendar::$plugin_textdom); ?></th>
					<td style="vertical-align: middle;">
						<?php if ($view) { 
							echo $evt->date_admin_from.(!$evt->allday ? ' '.$evt->time_admin_from : '');
						} else { ?>
							<input type="text"
						    	id="fse_datepicker_from<?php echo ($view ? 'dmy' : ''); ?>" 
						    	name="event_from" 
						    	size="10"
						    	value="<?php echo $evt->date_admin_from; ?>" 
						    	onchange="if (fse_validateDate(this, '<?php echo $df; ?>','<?php echo $ds; ?>') == false) { 
						    		this.focus(); 
						    		this.value = ''; 
						    		alert('<?php _e('Please enter a valid date', fsCalendar::$plugin_textdom); ?>.') 
						    		}; fse_updateOtherDate(this, '<?php echo $df; ?>','<?php echo $ds; ?>');"  
						    	onfocus="this.select();" 
						    	<?php echo ($gc_enabled ? "onkeydown=\"jQuery('#fse_datepicker_from').datepicker('hide')\"" : '' ); ?> />
						    <input type="text"
						    	id="time_from"
						    	name="event_tfrom"
						    	size="5" 
						    	value="<?php echo $evt->time_admin_from; ?>" 
						    	onblur="if (fse_validateTime(this) == false) { 
						    		this.focus(); 
						    		this.value = ''; 
						    		alert('<?php _e('Please enter a valid time', fsCalendar::$plugin_textdom); ?>.') 
						    		} fse_updateOtherTime(this, '<?php echo $df; ?>','<?php echo $ds; ?>');" />
					    <?php } ?>
					</td>
				</tr>
				<tr>
					<th scope="row" style="vertical-align: middle;"><?php _e('To', fsCalendar::$plugin_textdom); ?></th>
					<td style="vertical-align: middle;">
						<?php if ($view) { 
							echo $evt->date_admin_to.(!$evt->allday ? ' '.$evt->time_admin_to : '');
						} else { ?>
							<input type="text"
						    	id="fse_datepicker_to<?php echo ($view ? 'dmy' : ''); ?>" 
						    	name="event_to" 
						    	size="10"
						    	value="<?php echo $evt->date_admin_to; ?>" 
						    	onchange="if (fse_validateDate(this, '<?php echo $df; ?>','<?php echo $ds; ?>') == false) { 
						    		this.focus(); 
						    		this.value = ''; 
						    		alert('<?php _e('Please enter a valid date', fsCalendar::$plugin_textdom); ?>.') 
						    		};fse_updateOtherDate(this, '<?php echo $df; ?>','<?php echo $ds; ?>');"
						    	onfocus="this.select();"   
						    	<?php echo ($gc_enabled ? "onkeydown=\"jQuery('#fse_datepicker_to').datepicker('hide')\"" : '' ); ?> />
						    <input type="text"
						    	id="time_to"
						    	name="event_tto"
						    	size="5"
						    	value="<?php echo $evt->time_admin_to; ?>" 
						    	onblur="if (fse_validateTime(this) == false) { 
						    		this.focus(); 
						    		this.value = ''; 
						    		alert('<?php _e('Please enter a valid time', fsCalendar::$plugin_textdom); ?>.') 
						    		} fse_updateOtherTime(this, '<?php echo $df; ?>','<?php echo $ds; ?>');" />
						<?php } ?>
					</td>
				</tr>
				<tr>
					<td colspan="2">
					    <input type="checkbox"
					    	id="allday"
					    	name="event_allday"
					    	onclick="fse_toogleAllday(this.checked);" 
					    	<?php echo ($evt->allday == true ? 'checked="checked"' : ''); ?> 
					    	<?php echo ($view ? 'disabled="disabled"' : ''); ?>/>
					    <label for="allday"><?php _e('All day event', fsCalendar::$plugin_textdom)?></label>
					</td>
				</tr>
				
			</tbody>
		</table>
		
		<script type="text/javascript">
		jQuery(document).ready(function() {
			<?php if ($gc_enabled == 1) { 
				?>
				jQuery('#fse_datepicker_from').datepicker(
						{dateFormat: '<?php echo $f; ?>'
							<?php echo (get_option('fse_adm_gc_show_week') == 1 ? ',showWeek: true' : '');?>
							<?php echo (get_option('fse_adm_gc_show_sel') == 1 ? ',changeMonth: true, changeYear: true' : '');?>
							, showOn: <?php echo ($gc_mode == 0 ? "'focus'" : ($gc_mode == 1 ? "'button'" : "'both'")); ?>
							<?php echo (($gc_mode == 1 || $gc_mode == 2) == 1 ? ", buttonImage: '".fsCalendar::$plugin_img_url."calendar.png', buttonImageOnly: true" : '');?>
							, duration: 0
							});
				jQuery('#fse_datepicker_to').datepicker(
						{dateFormat: '<?php echo $f; ?>'
							<?php echo (get_option('fse_adm_gc_show_week') == 1 ? ',showWeek: true' : '');?>
							<?php echo (get_option('fse_adm_gc_show_sel') == 1 ? ',changeMonth: true, changeYear: true' : '');?>
							, showOn: <?php echo ($gc_mode == 0 ? "'focus'" : ($gc_mode == 1 ? "'button'" : "'both'")); ?>
							<?php echo (($gc_mode == 1 || $gc_mode == 2) == 1 ? ", buttonImage: '".fsCalendar::$plugin_img_url."calendar.png', buttonImageOnly: true" : '');?>
							, duration: 0
							});
			<?php } ?>
			fse_toogleAllday(jQuery('#allday').attr('checked'));
		});
		</script>
		
		<?php 
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
			<div class="alignleft ">
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
					<option value="all"<?php echo (!isset($filter['datefrom']) ? ' selected="selected"' : ''); ?>><?php _e('Show all dates', fsCalendar::$plugin_textdom); ?></option>
					<option value="future"<?php echo (isset($filter['datefrom']) && !isset($filter['dateto']) ? ' selected="selected"' : ''); ?>><?php _e('Show future dates only', fsCalendar::$plugin_textdom); ?></option>
					<?php 
					$min = $wpdb->get_var('SELECT MIN(datefrom) AS min FROM '.$wpdb->prefix.'fsevents');
					$max = $wpdb->get_var('SELECT MAX(dateto)    AS max FROM '.$wpdb->prefix.'fsevents');
					
					echo $min.'-'.$max.'--';
					
					if ($min != NULL && $max != NULL) {
						$ms = mysql2date('m', $min);
						$ys = mysql2date('Y', $min);
						$me = mysql2date('m', $max);
						$ye = mysql2date('Y', $max);
						
						while($ys <= $ye) {
							while($ms<=12 && ($ys < $ye || $ms <= $me)) {
								$time = mktime(0, 0, 0, $ms, 1, $ys);
								$date = date('Y-m-d H:i:s', $time);
								echo '<option value="'.$date.'"'.($date == $filter['datefrom'] ? ' selected="selected"' : '').'>'.date_i18n('F Y', $time).'</option>';
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
											  'orderby' => 'name');
					if (isset($filter['categories'][0])) {
						$dropdown_options['selected'] = $filter['categories'][0];
					}
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

function fse_ValidateDate($date, $fmt, $ret_sep = false) {
	
	if (strpos($fmt, '.') !== false) {
		$sep = '.';
	} elseif (strpos($fmt, '-') !== false) {
		$sep = '-';
	} elseif (strpos($fmt, '/') !== false) {
		$sep = '/';
	} else {
		return false;
	}
	
	$fmt_t = explode($sep, $fmt);
	
	$dat_t = explode($sep, $date);
	
	if (count($fmt_t) <> count($dat_t)) {
		return false;
	}
	
	$ret = '';
	foreach($dat_t as $k => $t) {
		$t = intval($t);
		$typ = $fmt_t[$k];
		if ($t < 1) {
			return false;
		}
		switch($typ) {
			case 'd':
				$day = $t;
				break;
			case 'm':
				if ($t > 12) {
					return false;
				}
				$month = $t;
				break;
			case 'Y':
				if ($t < 99) {
					if ($t >= 70) {
						$t+=1900;
					} else {
						$t+=2000;
					}
				}
				if ($t < 1970) {
					return false;
				}
				$year = $t;
				break;
			default:
				return false;
		}
	}
	
	if (empty($day) || empty($month) || empty($year)) {
		return false;
	}
	// Validate date by creating it. If the day changes, the date is
	// invalid
	$ts = mktime(0,0,0,$month, $day, $year);
	if (intval(date('d', $ts)) <> $day) {
		return false;
	}
	
	if ($ret_sep == true) {
		return array('d'=>$day, 'm'=>$month, 'y'=>$year);
	} else {
		return date_i18n($fmt, $ts);
	}
}

function fse_ValidateTime($time, $ret_sep = false) {
	if (strpos($time, ':') !== false) {
		list($h, $m) = explode(':', $time);	
	} elseif (strpos($time,'.') !== false) {
		list($h, $m) = explode(':', $time);
	} elseif (strlen($time) == 4) {
		$h = substr($time, 0, 2);
		$m = substr($time, 2, 2);
	} elseif (strlen($time) == 3) {
		$h = substr($time, 0, 1);
		$m = substr($time, 1, 2);
	} else {
		return false;
	}
	
	if ($h < 0 || $h > 23) {
		return false;
	}
	if ($m < 0 || $m > 59) {
		return false;
	}
	
	if ($ret_sep == true) {
		return array('h'=>intval($h), 'm'=>intval($m));
	}
	
	return sprintf("%02d:%02d", $h, $m);
}
?>