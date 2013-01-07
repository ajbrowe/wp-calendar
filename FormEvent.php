<?php 
if ( !defined('ABSPATH') )
	die('-1');

$steps = 15; // TODO: In Options page
$add_min = 20;
$add_hour = 1;

$action = (isset($_GET['action']) ? $_GET['action'] : '');

$fatal = $errors = $success = array();

// Get Post Data
if (isset($_POST['eventid']) && $action != 'view') {
	// Get all post data
	$evt->eventid     = intval($_POST['eventid']);
	
	// Generate Objekt
	$evt = new fsEvent($evt->eventid);
	
	$evt->date_admin_from   = $_POST['event_from'];
	$evt->date_admin_to     = $_POST['event_to'];
	
	$evt->allday     		= (isset($_POST['event_allday']) ? 1 : 0);
	
	if (!$evt->allday) {
		$evt->time_admin_from   = $_POST['event_tfrom'];
		$evt->time_admin_to     = $_POST['event_tto'];
	}
	$evt->location    		= $_POST['event_location'];
	if (isset($_POST['content'])) {
		$evt->description 		= $_POST['content'];
	}
	$evt->subject     		= $_POST['event_subject'];
	$evt->state       		= $_POST['event_state'];
	$evt->categories  		= $_POST['post_category'];
	
	foreach($evt as $k => $v) {
		if (is_string($v)) {
			$evt->{$k} = stripslashes($v);
		}
	}
	
	$referer = $_POST['referer'];
} else {
	if (isset($_GET['event'])) {
		$evt = new fsEvent(intval($_GET['event']));
	} else {
		$evt = new fsEvent(0);
	}
	
}

$copy = false;
if ($action == 'copy') {
	// Behave like a new one
	unset($evt->eventid);
	$action = 'new';
	$copy = true;
}
if ($action == 'new') {
	if ($evt->eventid > 0) {
		$action = 'edit';
	} else {
		if (!$fsCalendar->userCanAddEvents()) {
			$fatal[] = __('No permission to create event', fsCalendar::$plugin_textdom);
		}
	}
}
if ($action == 'edit') {
	if (empty($evt->eventid)) {
		$fatal[] = __('Event does not exist');
	} else {
		if (!$evt->userCanEditEvent()) {
			if ($fsCalendar->userCanViewEvents())
				$action = 'view';
			else
				$fatal[] = __('No permission to edit event', fsCalendar::$plugin_textdom);
		}
	}
}
if ($action == 'view') {
	if (empty($evt->eventid)) {
		$fatal[] = __('Event does not exist');
	} else {
		if (!$evt->userCanViewEvent()) {
			$fatal[] = __('No permission to view event', fsCalendar::$plugin_textdom);
		}
	}
}

// Verify Nonce
if (isset($_POST['eventid']) && $action != 'view') {
	$nonce = $_POST['_fseevent'];
	if (!wp_verify_nonce($nonce, 'event'))
		$fatal[] = __('Security check failed', fsCalendar::$plugin_textdom); 
}

if (!isset($fatal) || (is_array($fatal) && count($fatal) == 0)) {
	if (isset($_POST['eventid']) && $action != 'view') {	
		// Save post
		if (isset($_POST['save']) || (isset($_POST['publish']) && empty($evt->eventid))) {
			if (($res = $evt->saveToDatabase()) === true) {
				if ($action == 'edit') {
					$success[] = __('Event updated', fsCalendar::$plugin_textdom);
				} else {
					$success[] = __('New event saved', fsCalendar::$plugin_textdom);
					$action = 'edit';
				}
			} else {
				$errors[] = $res;
			}
		} // End Save
		
		// Publish
		if (isset($_POST['publish']) && !empty($evt->eventid)) {
			if (($res = $evt->setStatePublished()) === true) {
				$success[] = __('Event published', fsCalendar::$plugin_textdom);
				
				// Check again if user has right to edit that one
				if (!$evt->userCanEditEvent()) {
					$action = 'view';
					$success[] = __("Automatically switched to view mode beacause you don't have permissions to edit a published event", fsCalendar::$plugin_textdom);
				}
			} else {
				$errors[] = $res;
			}
		}
		
		if (isset($_POST['jsaction'])) {
			switch($_POST['jsaction']) {
				case 'draft':
					if (($res = $evt->setStateDraft()) === true) {
						$success[] = __('Event set to draft state', fsCalendar::$plugin_textdom);
					} else {
						$errors[] = $res;
					}
					break;
				case 'nosync':
					$evt->disableSynchronization();
					$success[] = __('Synchronization with post data has been disabled', fsCalendar::$plugin_textdom);
					break;
			}
		}
	} else {
		if ($evt->eventid == 0 && !$copy) {
			// Calculate date and time
			$day = date('d');
			$mon = date('m');
			$yea = date('Y');
			$std = date('H');
			$min = date('i');
			
			// No changes
			if ($min > 0) {
				$min = ceil($min / $steps) * $steps;
				if ($min == 0) {
					$std++;
				}
				$current = mktime($std, $min, 0, $mon, $day, $yea);
			}
			$evt->date_admin_from = date_i18n($evt->date_admin_format, $current);
			
			$default_start_time = get_option('fse_adm_default_start_time');
			
			if (!empty($default_start_time)) {
				$evt->time_admin_from = $default_start_time;	
			} else {
				$evt->time_admin_from = date_i18n($evt->time_admin_format, $current);
			}
			
			$default_end_time = get_option('fse_adm_default_end_time');
			
			if (!empty($default_end_time)) {
				$evt->date_admin_to = $evt->date_admin_from;
				$evt->time_admin_to = $default_end_time;
			} else {
				$min += $add_min;
				if ($min >= 60) {
					$std++;
					$min -= 60;
				}
				$std += $add_hour;
				$future = mktime($std, $min, 0, $mon, $day, $yea);
				$evt->date_admin_to = date_i18n($evt->date_admin_format, $future);
				$evt->time_admin_to = date_i18n($evt->time_admin_format, $future);
			}
			$evt->allday    = false;
			
			
			$evt->location = '';
			$evt->description = '';
			$evt->categories = array();
			$evt->state = 'draft';
		} elseif ($copy) {
			// Reset some date whe copy
			$evt->state = 'draft';
			unset($evt->createdate);
			unset($evt->author);
			unset($evt->publishauthor);
			unset($evt->publishdate);
			unset($evt->author_t);
			unset($evt->publishauthor_t);
			
		}
		
		// Referer holen
		$referer = wp_get_referer();
		
		// Nur wenn es sich um einen internen Referer handelt
		if (strpos($referer, fsCalendar::$plugin_filename) === false) { 
			$referer = '';
		} elseif (strpos($referer, 'action=delete') !== false) {
			$referer = str_replace('&action=delete', '', $referer);
		} elseif (strpos($referer, 'action') !== false) {
			$referer = '';
		}
		
	} // End DB/Post Weiche	
	
} // End Fatal Error Skip


// If Post is synchronized, show a message
if ($action == 'edit' && !isset($_POST['eventid']) && !empty($evt->eventid) && $evt->updatedbypost == true) {
	$disabled_sync = true;
	$success[] = sprintf(__('Some fields are disabled, because this event is linked to <a href="post.php?post=%d&action=edit">this post</a>.', fsCalendar::$plugin_textdom), $evt->postid).' '.
			     __('Click '."<a class=\"hide-if-no-js\" href=\"\" onClick=\"document.forms['event'].jsaction.value='nosync'; document.forms['event'].submit(); return false;\"'>".'here</a> to disable post synchronization to edit this event.', fsCalendar::$plugin_textdom);
} else {
	$disabled_sync = false;
}
	
echo $this->pageStart(($evt->eventid == 0 ? __('Add New Event', fsCalendar::$plugin_textdom) : ($action == 'view' ? __('View Event', fsCalendar::$plugin_textdom) : __('Edit Event', fsCalendar::$plugin_textdom))), '', 'icon-edit');
	
	// Check DB Version
$dbver = get_option('fse_db_version', -1);
if ($dbver < FSE_DB_VERSION) {
	
} else {
	// Bei Fatal Errors gleich wieder raus!
	if (count($fatal) > 0) {
		echo '<div id="notice" class="error"><p>';
		foreach($fatal as $f) {
			echo $f.'<br />';
		}
		echo '</p></div>';
		echo $this->pageEnd();
		return;	
	}
	if (count($errors) > 0) {
		echo '<div id="notice" class="error"><p>';
		foreach($errors as $e) {
			echo $e.'<br />';
		}
		echo '</p></div>';
	}
	if (count($success) > 0) {
		echo '<div id="message" class="updated fade"><p>';
		foreach($success as $e) {
			echo $e.'<br />';
		}
		echo '</p></div>';
	}
	
	
	?>
	
	<form id="event" method="post" action="" name="event">
	<div id="poststuff" class="metabox-holder has-right-sidebar">
		<div id="side-info-column" class="inner-sidebar">
			<div id="side-sortables" class="meta-box-sortables ui-sortable">
			
			<?php echo $this->pagePostBoxStart('state', __('Publish State', fsCalendar::$plugin_textdom)); ?>
			<div id="submitpost" class="submitbox">
				
				<?php if ($action != 'view' && $evt->state == 'draft' && $evt->userCanPublishEvent()) { ?>
				<div id="minor-publishing-actions">
					<div id="save-action">
						<?php if (empty($evt->eventid)) { ?>
							<input id="save-post" class="button button-highlighted" type="submit" value="Save Draft" name="save"/>
						<?php } else { ?>
							<input id="save-post" class="button button-highlighted" type="submit" value="Save" name="save"/>
						<?php } ?>
					</div>
					<div class="clear"/></div>
				</div>
				<?php } ?>
				
				<div id="minor-publishing">
					<div id="misc-publishing-actions">
						<div class="misc-pub-section">
							<?php _e('State', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display">
							<?php echo fsCalendar::$valid_states[$evt->state]; ?></span>
							<?php if ($action != 'view' && $evt->state ==  'publish' && $evt->userCanEditEvent()) { ?>
							<a class="hide-if-no-js" href="" onClick="document.forms['event'].jsaction.value='draft'; document.forms['event'].submit(); return false;"><?php _e('Change to Draft', fsCalendar::$plugin_textdom)?></a>
							<?php } ?>
						</div>
						<div class="misc-pub-section">
							<?php _e('Created by', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (empty($evt->author_t) ? '-' : esc_attr($evt->author_t)); ?></span>
						</div>
						<div class="misc-pub-section">
							<?php _e('Created', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->createdate) ? mysql2date($evt->date_time_format, $evt->createdate) : '-'); ?></span>
						</div>
						<div class="misc-pub-section">
							<?php _e('Published by', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (empty($evt->publishauthor_t) ? '-' : esc_attr($evt->publishauthor_t)); ?></span>
						</div>
						<div class="misc-pub-section">
							<?php _e('Published', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->publishdate) ? mysql2date($evt->date_time_format, $evt->publishdate) : '-'); ?></span>
						</div>
						<?php 
						if ($action != 'view' && $evt->updatedbypost == true) {
							echo '<div class="misc-pub-section">';
							echo sprintf(__('This event is synchronized with <a href="post.php?post=%d&action=edit">this post</a>. Click '."<a class=\"hide-if-no-js\" href=\"\" onClick=\"document.forms['event'].jsaction.value='nosync'; document.forms['event'].submit(); return false;\"'>".'here</a> to disable the synchronization.', fsCalendar::$plugin_textdom), $evt->postid);
							echo '</div>';
						}
						?>
					</div>
					<div class="clear"/></div>
				</div>
			
				<?php if ($action != 'view' || $evt->userCanEditEvent() ) { ?>
				<div id="major-publishing-actions">
					<div id="publishing-action">
						<?php
						if ($action == 'view') { 
							echo '<input id="save" class="button-primary" type="button" value="'.__('Edit', fsCalendar::$plugin_textdom).'"'." name=\"changetoedit\" onClick=\"document.location.href=document.location.href.replace(/action=view/, 'action=edit')\" />";
						} elseif ($evt->state == 'publish') {
							echo '<input id="save" class="button-primary" type="submit" value="'.__('Save', fsCalendar::$plugin_textdom).'" name="save" />';
						} elseif ( $evt->userCanPublishEvent() ) {
							echo '<input id="publish" class="button-primary" type="submit" value="'.__('Publish', fsCalendar::$plugin_textdom).'" name="publish" />';
						} elseif ( $evt->eventid > 0 ) {
							echo '<input id="save" class="button-primary" type="submit" value="'.__('Save', fsCalendar::$plugin_textdom).'" name="save" />';
						} else {
							echo '<input id="save" class="button-primary" type="submit" value="'.__('Save Draft', fsCalendar::$plugin_textdom).'" name="save" />';
						}
						?>
					</div>
					<div class="clear"></div>
				</div>
				<?php } ?>
			</div>
			
			<?php echo $this->pagePostBoxEnd(); ?>
			
			<?php echo $this->pagePostBoxStart('date', __('When', fsCalendar::$plugin_textdom)); ?>
			<?php echo $this->postBoxDateAndTime($evt, ($action == 'view' ? true : false)); ?>		
			<?php echo $this->pagePostBoxEnd();	?>
			
			<?php echo $this->pagePostBoxStart('categorydiv', __('Categories', fsCalendar::$plugin_textdom)); ?>
			<?php $this->postBoxCategories($evt->categories, (($action == 'view' || $evt->updatedbypost == true) ? true : false)); ?>		
			<?php echo $this->pagePostBoxEnd();	?>
			</div>
		</div>
		<div id="post-body">
			<div id="post-body-content">
				<p>
				<?php _e('Subject', fsCalendar::$plugin_textdom); ?>
				<input id="title" 
					type="text"
					value="<?php echo esc_attr($evt->subject); ?>" 
					tabindex="1" 
					name="event_subject" 
					maxlength="255" 
					style="font-size: 1.7em; width: 100%;" 
					<?php echo ($action=='view' || $evt->updatedbypost == true ? 'readonly="readonly"' : ''); ?>/>
				</p>
				<p>
				<?php _e('Location', fsCalendar::$plugin_textdom); ?>
				<input id="location" 
					type="text"
					value="<?php echo esc_attr($evt->location); ?>" 
					tabindex="2" 
					name="event_location" 
					maxlength="255" 
					style="width: 100%;" 
					<?php echo ($action=='view' ? 'readonly="readonly"' : ''); ?>/>
				</p>
				<?php if ($action == 'view' || $evt->updatedbypost == true) { ?>
					Description
					<hr size="1" color="#DFDFDF" />
					<div id="postdiv" class="postarea"><?php echo apply_filters('the_content', $evt->description); ?></div>
					<input type="hidden" name="event_desc" value="<?php echo esc_attr($evt->description); ?>" />
				<?php } else { ?>
					<?php
					if (function_exists('wp_editor')) { // WP 3.3+
						wp_editor( $evt->description, 'content' );
					} else {
					?>
						<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
						<?php the_editor($evt->description, 'content'); // This has a parameter for TABINDEX ?>
						</div>
					<?php
					}
					?> 

				<?php } ?>
			</div>
		</div>
		<br class="clear"/>
	</div>
	<input type="hidden" name="eventid" value="<?php echo $evt->eventid; ?>" />
	<input type="hidden" name="event_state" value="<?php echo $evt->state; ?>" />
	<input type="hidden" name="referer" value="<?php echo $referer; ?>" />
	<input type="hidden" name="jsaction" value="" />
	<?php wp_nonce_field('event', '_fseevent'); ?>
	
	<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery('input#title').autocomplete(
				{source: ajaxurl+"?action=fs_subject_ac_cb"});
		jQuery('input#location').autocomplete(
				{source: ajaxurl+"?action=fs_location_ac_cb"});
	});
	</script>
	
	</form>
<?php
}
echo $this->pageEnd();
?>