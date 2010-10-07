<?php 
if ( !defined('ABSPATH') )
	die('-1');

$steps = 15; // TODO: In Options page
$add_min = 20;
$add_hour = 1;

$df = get_option('fse_df_admin');
$ds = get_option('fse_df_admin_sep');

$gc_enabled = get_option('fse_adm_gc_enabled');
$gc_mode = get_option('fse_adm_gc_mode');

$action = $_GET['action'];

// Get Post Data
if (isset($_POST['eventid']) && $action != 'view') {
	// Get all post data
	$evt->eventid     = intval($_POST['eventid']);
	
	// Generate Objekt
	$evt = new fsEvent($evt->eventid);
	
	$evt->date_admin_from   = $_POST['event_from'];
	$evt->date_admin_to     = $_POST['event_to'];
	$evt->time_admin_from   = $_POST['event_tfrom'];
	$evt->time_admin_to     = $_POST['event_tto'];
	$evt->location    		= $_POST['event_location'];
	$evt->description 		= $_POST['event_desc'];
	$evt->subject     		= $_POST['event_subject'];
	$evt->state       		= $_POST['event_state'];
	$evt->categories  		= $_POST['post_category'];
	$evt->allday     		= (isset($_POST['event_allday']) ? 1 : 0);
	
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
		//print_r($evt);
		
		// Save post
		if (isset($_POST['save']) || (isset($_POST['publish']) && empty($evt->eventid))) {
			if (!is_array($evt->categories)) {
				$evt->categories = array(1); // Uncategorized
			}
			
			// Vaidate subject
			if (empty($evt->subject)) {
				$errors[] = __('Please enter a subject', fsCalendar::$plugin_textdom);
			}
			// Validate date/time
			$ret_df = fse_ValidateDate($evt->date_admin_from, $evt->date_admin_format);
			if ($ret_df === false) {
				$errors[] = __('Please enter a valid `from` date', fsCalendar::$plugin_textdom);
			} else {
				$evt->date_admin_from = $ret_df;
			}
			if ($evt->allday == 0) {
				$ret_tf = fse_ValidateTime($evt->time_admin_from);
				if ($ret_tf === false) {
					$errors[] = __('Please enter a valid `from` time', fsCalendar::$plugin_textdom);
				} else {
					$evt->time_admin_from = $ret_tf;
				}
			} else {
				$evt->time_admin_from = '00:00';
			}
			$ret_dt = fse_ValidateDate($evt->date_admin_to, $evt->date_admin_format);
			if ($ret_dt === false) {
				$errors[] = __('Please enter a valid `to` date', fsCalendar::$plugin_textdom);
			} else {
				$evt->date_admin_to = $ret_dt;
			}
			if ($evt->allday == 0) {
				$ret_tt = fse_ValidateTime($evt->time_admin_to);
				if ($ret_tt === false) {
					$errors[] = __('Please enter a valid `to` time', fsCalendar::$plugin_textdom);
				} else {
					$evt->time_admin_to = $ret_tt;
				}
			} else {
				$evt->time_admin_to = '00:00';
			}
			
			$fd = fse_ValidateDate($evt->date_admin_from, $evt->date_admin_format, true);
			$ft = fse_ValidateTime($evt->time_admin_from, true);
			$td = fse_ValidateDate($evt->date_admin_to, $evt->date_admin_format, true);
			$tt = fse_ValidateTime($evt->time_admin_to, true);
			
			$ts_from = mktime($ft['h'], $ft['m'], 0, $fd['m'], $fd['d'], $fd['y']);
			$ts_to   = mktime($tt['h'], $tt['m'], 0, $td['m'], $td['d'], $td['y']);
			
			if ($ts_from > $ts_to) {
				$errors[] = __('End is before start', fsCalendar::$plugin_textdom);
			}
			
			// No errors -> Insert/Update
			if (!isset($errors) || count($errors) == 0) {
								
				if ($evt->eventid > 0) {
					// Check authority
					if ($evt->userCanEditEvent()) {
						$sql = $wpdb->prepare("UPDATE ".$wpdb->prefix.'fsevents '."
							SET subject=%s, tsfrom=$ts_from, tsto=$ts_to, allday=$evt->allday, description=%s, location=%s, state=%s 
							WHERE eventid=$evt->eventid",
				        	$evt->subject, $evt->description, $evt->location, $evt->state);
					} else {
						$errors[] = __('No permission to edit event', fsCalendar::$plugin_textdom);
					}
				} else {
					if ($fsCalendar->userCanAddEvents()) {
						$time = time();
						$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix.'fsevents '."
							(subject, tsfrom, tsto, allday, description, location, author, createdate, state)
							VALUES (%s, $ts_from, $ts_to, $evt->allday, %s, %s, $user_ID, $time, %s)", 
				        	$evt->subject, $evt->description, $evt->location, $evt->state);
					} else {
						$errors[] = __('No permission to create event', fsCalendar::$plugin_textdom);
					}
				}
		        
				
		        if ($wpdb->query($sql) !== false) {
		        	if ($evt->eventid <= 0) {
			        	$success[] = __('New event saved', fsCalendar::$plugin_textdom);
			        	$evt->eventid = $wpdb->insert_id;
			        	
			        	$evt->author = $user_ID;
			        	$evt->createdate = $time;
			        	
			        	$u = new WP_User($user_ID);
			        	$evt->author_t = $u->display_name;
			        	unset($u);
			        	
			        	$action = 'edit'; // Switch to edit mode!
		        	} else {
		        		$success[] = __('Event updated', fsCalendar::$plugin_textdom);
		        	}
		        	
		        	
		        	// Handle categories
		        	$ret_cats = $wpdb->get_col('SELECT catid FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$evt->eventid);
		        	if (!is_array($ret_cats)) {
		        		$ret_cats = array();
		        	}
		        	
		        	// Insert missing
		        	foreach($evt->categories as $c) {
		        		if (!in_array($c, $ret_cats)) {
		        			$sql = 'INSERT INTO '.$wpdb->prefix.'fsevents_cats VALUES ('.$evt->eventid.','.$c.')';
		        			$wpdb->query($sql);
		        		}
		        	}
		        	// Remove old
		        	foreach($ret_cats as $c) {
		        		if (!in_array($c, $evt->categories)) {
		        			$sql = 'DELETE FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$evt->eventid.' AND catid='.$c;
		        			$wpdb->query($sql);
		        		}
		        	}
		        	
		        } else {
		        	$errors[] = __('DB Error', fsCalendar::$plugin_textdom); 
		        }
		        
			}
		} // End Save
		
		// Publish
		if (isset($_POST['publish']) && !empty($evt->eventid)) {
			if ($evt->userCanEditEvent()) {
				$time = time();
				if ($wpdb->query('UPDATE '.$wpdb->prefix.'fsevents '.' 
								  SET state="publish", publishauthor="'.intval($user_ID).'", publishdate='.$time.' 
								  WHERE eventid='.$evt->eventid) !== false) {
					$success[] = __('Event published', fsCalendar::$plugin_textdom);
					$evt->state = 'publish';
					$evt->publishauthor = $user_ID;
					$evt->publishdate   = $time;
					$u = new WP_User($user_ID);
					$evt->publishauthor_t = $u->display_name;
					unset($u);
					
					// Check again, if user can edit date
					if (!$evt->userCanEditEvent()) {
						$action = 'view';
						$success[] = __("Automatically switched to view mode beacause you don't have permissions to edit a published event", fsCalendar::$plugin_textdom);
					}
					
				} else {
					$errors[] = __('Event could not be published', fsCalendar::$plugin_textdom);
				}
			} else {
				$errors[] = __('No permission to edit event', fsCalendar::$plugin_textdom);
			}
		}
		
		if (isset($_POST['jsaction'])) {
			switch($_POST['jsaction']) {
				case 'draft':
					if ($evt->userCanEditEvent()) {
						
						if ($wpdb->query('UPDATE '.$wpdb->prefix.'fsevents '.' 
										  SET state="draft", publishdate=NULL, publishauthor=NULL 
										  WHERE eventid='.$evt->eventid) !== false) {
							$success[] = __('Event set to draft state', fsCalendar::$plugin_textdom);
							$evt->state = 'draft';
							$evt->publishauthor = '';
							$evt->publishauthor_t = '';
							$evt->publishdate = '';
							
						} else {
							$errors[] = __('Event could not be set to draft state', fsCalendar::$plugin_textdom);
						}
						
					} else {
						$errors[] = __('No permission to edit event', fsCalendar::$plugin_textdom);
					}
			}
		}
	} else {
		if ($evt->eventid == 0 && !$copy) {
			// Calculate date and time
			$current = time();
			$day = fsCalendar::date('d', $current);
			$mon = fsCalendar::date('m', $current);
			$yea = fsCalendar::date('Y', $current);
			$std = fsCalendar::date('H', $current);
			$min = fsCalendar::date('i', $current);
			
			// No changes
			if ($min > 0) {
				$min = ceil($min / $steps) * $steps;
				if ($min == 0) {
					$std++;
				}
				$current = mktime($std, $min, 0, $mon, $day, $yea);
			}
			$evt->date_admin_from = fsCalendar::date_i18n($evt->date_admin_format, $current);
			$evt->time_admin_from = fsCalendar::date_i18n($evt->time_admin_format, $current);
			
			// End date/time
			$min += $add_min;
			if ($min >= 60) {
				$std++;
				$min -= 60;
			}
			$std += $add_hour;
			$future = mktime($std, $min, 0, $mon, $day, $yea);
			$evt->date_admin_to = fsCalendar::date_i18n($evt->date_admin_format, $future);
			$evt->time_admin_to = fsCalendar::date_i18n($evt->time_admin_format, $future);
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

echo $this->pageStart(($evt->eventid == 0 ? __('Add New Event', fsCalendar::$plugin_textdom) : ($action == 'view' ? __('View Event', fsCalendar::$plugin_textdom) : __('Edit Event', fsCalendar::$plugin_textdom))), '', 'icon-edit');

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
						<?php _e('Created', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->createdate) ? fsCalendar::date_i18n($evt->date_time_format, $evt->createdate) : '-'); ?></span>
					</div>
					<div class="misc-pub-section">
						<?php _e('Published by', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (empty($evt->publishauthor_t) ? '-' : esc_attr($evt->publishauthor_t)); ?></span>
					</div>
					<div class="misc-pub-section">
						<?php _e('Published', fsCalendar::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->publishdate) ? fsCalendar::date_i18n($evt->date_time_format, $evt->publishdate) : '-'); ?></span>
					</div>
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
			<table class="fs-table">
				<tbody>
					<tr>
						<th scope="row" style="vertical-align: middle;"><?php _e('From', fsCalendar::$plugin_textdom); ?></th>
						<td style="vertical-align: middle;">
							<?php if ($action == 'view') { 
								echo $evt->date_admin_from.(!$evt->allday ? ' '.$evt->time_admin_from : '');
							} else { ?>
								<input type="text"
							    	id="fse_datepicker_from<?php echo ($action=='view' ? 'dmy' : ''); ?>" 
							    	name="event_from" 
							    	size="10"
							    	value="<?php echo $evt->date_admin_from; ?>" 
							    	onchange="if (fse_validateDate(this, '<?php echo $df; ?>','<?php echo $ds; ?>') == false) { 
							    		this.focus(); 
							    		this.value = ''; 
							    		alert('Bitte geben Sie ein korrektes Datum ein.') 
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
							    		alert('Bitte geben Sie eine korrekte Uhrzeit ein.') 
							    		} fse_updateOtherTime(this, '<?php echo $df; ?>','<?php echo $ds; ?>');" />
						    <?php } ?>
						</td>
					</tr>
					<tr>
						<th scope="row" style="vertical-align: middle;"><?php _e('To', fsCalendar::$plugin_textdom); ?></th>
						<td style="vertical-align: middle;">
							<?php if ($action == 'view') { 
								echo $evt->date_admin_to.(!$evt->allday ? ' '.$evt->time_admin_to : '');
							} else { ?>
								<input type="text"
							    	id="fse_datepicker_to<?php echo ($action=='view' ? 'dmy' : ''); ?>" 
							    	name="event_to" 
							    	size="10"
							    	value="<?php echo $evt->date_admin_to; ?>" 
							    	onchange="if (fse_validateDate(this, '<?php echo $df; ?>','<?php echo $ds; ?>') == false) { 
							    		this.focus(); 
							    		this.value = ''; 
							    		alert('Bitte geben Sie ein korrektes Datum ein.') 
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
							    		alert('Bitte geben Sie eine korrekte Uhrzeit ein.') 
							    		} fse_updateOtherTime(this, '<?php echo $df; ?>','<?php echo $ds; ?>');" />
							<?php } ?>
						</td>
					</tr>
					<tr>
						<td colspan="2">
						    <input type="checkbox"
						    	id="allday"
						    	name="event_allday"
						    	onclick="fse_toogleAllday(this);" 
						    	<?php echo ($evt->allday == true ? 'checked="checked"' : ''); ?> 
						    	<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
						    <label for="allday"><?php _e('All day event', fsCalendar::$plugin_textdom)?></label>
						</td>
					</tr>
				</tbody>
			</table>
		
		<?php echo $this->pagePostBoxEnd();	?>
		
		<?php echo $this->pagePostBoxStart('categorydiv', __('Categories', fsCalendar::$plugin_textdom)); ?>
		<?php $this->postBoxCategories($evt->categories, ($action == 'view' ? true : false)); ?>		
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
				<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
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
				<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
			</p>
			<?php if ($action == 'view') { ?>
				Description
				<hr size="1" color="#DFDFDF" />
				<div id="postdiv" class="postarea"><?php echo $evt->description ?></div>
			<?php } else { ?>
				<div id="<?php echo user_can_richedit() ? 'postdivrich' : 'postdiv'; ?>" class="postarea">
				<?php the_editor($evt->description, 'event_desc'); ?>
				</div>
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
</form>
<?php
$f = $evt->date_admin_format;
$f = str_replace('d', 'dd', $f);
$f = str_replace('m', 'mm', $f);
$f = str_replace('Y', 'yy', $f);
?>
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
					});
		jQuery('#fse_datepicker_to').datepicker(
				{dateFormat: '<?php echo $f; ?>'
					<?php echo (get_option('fse_adm_gc_show_week') == 1 ? ',showWeek: true' : '');?>
					<?php echo (get_option('fse_adm_gc_show_sel') == 1 ? ',changeMonth: true, changeYear: true' : '');?>
					, showOn: <?php echo ($gc_mode == 0 ? "'focus'" : ($gc_mode == 1 ? "'button'" : "'both'")); ?>
					<?php echo (($gc_mode == 1 || $gc_mode == 2) == 1 ? ", buttonImage: '".fsCalendar::$plugin_img_url."calendar.png', buttonImageOnly: true" : '');?>
					});
	<?php } ?>
	fse_toogleAllday(document.forms["event"].allday);
});
</script>

<?php

echo $this->pageEnd();

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
	if (intval(fsCalendar::date('d', $ts)) <> $day) {
		return false;
	}
	
	if ($ret_sep == true) {
		return array('d'=>$day, 'm'=>$month, 'y'=>$year);
	} else {
		return fsCalendar::date_i18n($fmt, $ts);
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