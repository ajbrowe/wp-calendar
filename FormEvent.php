<?php 
if ( !defined('ABSPATH') )
	die('-1');

$steps = 15; // TODO: In Options page
$add_min = 20;
$add_hour = 1;
$format = 'd.m.Y';

// Get Post Data
if (isset($_POST['eventid'])) {
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

$action = $_GET['action'];
if ($action == 'new') {
	if ($evt->eventid > 0) {
		$action = 'edit';
	} else {
		if (!$this->userCanAddEvents()) {
			$fatal[] = __('No permission to create event', self::$plugin_textdom);
		}
	}
}
if ($action == 'edit') {
	if (empty($evt->eventid)) {
		$fatal[] = __('Event does not exist');
	} else {
		if (!$evt->userCanEditEvent()) {
			if ($this->userCanViewEvents())
				$action = 'view';
			else
				$fatal[] = __('No permission to edit event', self::$plugin_textdom);
		}
	}
}
if ($action == 'view') {
	if (empty($evt->eventid)) {
		$fatal[] = __('Event does not exist');
	} else {
		if (!$evt->userCanViewEvent()) {
			$fatal[] = __('No permission to view event', self::$plugin_textdom);
		}
	}
}

// Verify Nonce
if (isset($_POST['eventid'])) {
	$nonce = $_POST['_fseevent'];
	if (!wp_verify_nonce($nonce, 'event'))
		$fatal[] = __('Security check failed', self::$plugin_textdom); 
}

if (!isset($fatal) || (is_array($fatal) && count($fatal) == 0)) {
	if (isset($_POST['eventid'])) {
		//print_r($evt);
		
		// Save post
		if (isset($_POST['save']) || (isset($_POST['publish']) && empty($evt->eventid))) {
			if (!is_array($evt->categories)) {
				$evt->categories = array(1); // Uncategorized
			}
			
			// Vaidate subject
			if (empty($evt->subject)) {
				$errors[] = __('Please enter a subject', self::$plugin_textdom);
			}
			// Validate date/time
			$ret_df = fse_ValidateDate($evt->date_admin_from, $evt->date_admin_format);
			if ($ret_df === false) {
				$errors[] = __('Please enter a valid `from` date', self::$plugin_textdom);
			} else {
				$evt->date_admin_from = $ret_df;
			}
			if ($evt->allday == 0) {
				$ret_tf = fse_ValidateTime($evt->time_admin_from);
				if ($ret_tf === false) {
					$errors[] = __('Please enter a valid `from` time', self::$plugin_textdom);
				} else {
					$evt->time_admin_from = $ret_tf;
				}
			} else {
				$evt->time_admin_from = '00:00';
			}
			$ret_dt = fse_ValidateDate($evt->date_admin_to, $evt->date_admin_format);
			if ($ret_dt === false) {
				$errors[] = __('Please enter a valid `to` date', self::$plugin_textdom);
			} else {
				$evt->date_admin_to = $ret_dt;
			}
			if ($evt->allday == 0) {
				$ret_tt = fse_ValidateTime($evt->time_admin_to);
				if ($ret_tt === false) {
					$errors[] = __('Please enter a valid `to` time', self::$plugin_textdom);
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
				$errors[] = __('End is before start', self::$plugin_textdom);
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
						$errors[] = __('No permission to edit event', self::$plugin_textdom);
					}
				} else {
					if ($this->userCanAddEvents()) {
						$time = time();
						$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix.'fsevents '."
							(subject, tsfrom, tsto, allday, description, location, author, createdate, state)
							VALUES (%s, $ts_from, $ts_to, $evt->allday, %s, %s, $user_ID, $time, %s)", 
				        	$evt->subject, $evt->description, $evt->location, $evt->state);
					} else {
						$errors[] = __('No permission to create event', self::$plugin_textdom);
					}
				}
		        
				
		        if ($wpdb->query($sql) !== false) {
		        	if ($evt->eventid <= 0) {
			        	$success[] = __('New event saved', self::$plugin_textdom);
			        	$evt->eventid = $wpdb->insert_id;
			        	
			        	$evt->author = $user_ID;
			        	$evt->createdate = $time;
			        	
			        	$u = new WP_User($user_ID);
			        	$evt->author_t = $u->display_name;
			        	unset($u);
			        	
			        	$action = 'edit'; // Switch to edit mode!
		        	} else {
		        		$success[] = __('Event updated', self::$plugin_textdom);
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
		        	$errors[] = __('DB Error', self::$plugin_textdom); 
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
					$success[] = __('Event published', self::$plugin_textdom);
					$evt->state = 'publish';
					$evt->publishauthor = $user_ID;
					$evt->publishdate   = $time;
					$u = new WP_User($user_ID);
					$evt->publishauthor_t = $u->display_name;
					unset($u);
					
					// Check again, if user can edit date
					if (!$evt->userCanEditEvent()) {
						$action = 'view';
						$success[] = __("Automatically switched to view mode beacause you don't have permissions to edit a published event", self::$plugin_textdom);
					}
					
				} else {
					$errors[] = __('Event could not be published', self::$plugin_textdom);
				}
			} else {
				$errors[] = __('No permission to edit event', self::$plugin_textdom);
			}
		}
		
		if (isset($_POST['jsaction'])) {
			switch($_POST['jsaction']) {
				case 'draft':
					if ($evt->userCanEditEvent()) {
						
						if ($wpdb->query('UPDATE '.$wpdb->prefix.'fsevents '.' 
										  SET state="draft", publishdate=NULL, publishauthor=NULL 
										  WHERE eventid='.$evt->eventid) !== false) {
							$success[] = __('Event set to draft state', self::$plugin_textdom);
							$evt->state = 'draft';
							$evt->publishauthor = '';
							$evt->publishauthor_t = '';
							$evt->publishdate = '';
							
						} else {
							$errors[] = __('Event could not be set to draft state', self::$plugin_textdom);
						}
						
					} else {
						$errors[] = __('No permission to edit event', self::$plugin_textdom);
					}
			}
		}
	} else {
		if ($evt->eventid == 0) {
			// Calculate date and time
			$current = time();
			$day = date('d', $current);
			$mon = date('m', $current);
			$yea = date('Y', $current);
			$std = date('H', $current);
			$min = date('i', $current);
			
			// No changes
			if ($min > 0) {
				$min = ceil($min / $steps) * $steps;
				if ($min == 0) {
					$std++;
				}
				$current = mktime($std, $min, 0, $mon, $day, $yea);
			}
			$evt->date_admin_from = date_i18n($evt->date_admin_format, $current);
			$evt->time_admin_from = date_i18n($evt->time_admin_format, $current);
			
			// End date/time
			$min += $add_min;
			if ($min >= 60) {
				$std++;
				$min -= 60;
			}
			$std += $add_hour;
			$future = mktime($std, $min, 0, $mon, $day, $yea);
			$evt->date_admin_to = date_i18n($evt->date_admin_format, $future);
			$evt->time_admin_to = date_i18n($evt->time_admin_format, $future);
			$evt->allday    = false;
			
			
			$evt->location = '';
			$evt->description = '';
			$evt->categories = array();
			$evt->state = 'draft';
		}
		
		// Referer holen
		$referer = wp_get_referer();
		
		// Nur wenn es sich um einen internen Referer handelt
		if (strpos($referer, self::$plugin_filename) === false) { 
			$referer = '';
		} elseif (strpos($referer, 'action=delete') !== false) {
			$referer = str_replace('&action=delete', '', $referer);
		} elseif (strpos($referer, 'action') !== false) {
			$referer = '';
		}
		
	} // End DB/Post Weiche	
	
} // End Fatal Error Skip

echo $this->pageStart(($evt->eventid == 0 ? __('Add New Event', self::$plugin_textdom) : __('Edit Event', self::$plugin_textdom)), '', 'icon-edit');

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
		
		<?php echo $this->pagePostBoxStart('state', __('Publish State', self::$plugin_textdom)); ?>
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
						<?php _e('State', self::$plugin_textdom); ?>: <span id="post-status-display">
						<?php echo self::$valid_states[$evt->state]; ?></span>
						<?php if ($evt->state ==  'publish' && $evt->userCanEditEvent()) { ?>
						<a class="hide-if-no-js" href="" onClick="document.forms['event'].jsaction.value='draft'; document.forms['event'].submit(); return false;"><?php _e('Change to Draft', self::$plugin_textdom)?></a>
						<?php } ?>
					</div>
					<div class="misc-pub-section">
						<?php _e('Created by', self::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (empty($evt->author_t) ? '-' : esc_attr($evt->author_t)); ?></span>
					</div>
					<div class="misc-pub-section">
						<?php _e('Created', self::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->createdate) ? date_i18n($evt->date_time_format, $evt->createdate) : '-'); ?></span>
					</div>
					<div class="misc-pub-section">
						<?php _e('Published by', self::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (empty($evt->publishauthor_t) ? '-' : esc_attr($evt->publishauthor_t)); ?></span>
					</div>
					<div class="misc-pub-section">
						<?php _e('Published', self::$plugin_textdom); ?>: <span id="post-status-display"> <?php echo (!empty($evt->publishdate) ? date_i18n($evt->date_time_format, $evt->publishdate) : '-'); ?></span>
					</div>
				</div>
				<div class="clear"/></div>
			</div>
		
			<div id="major-publishing-actions">
				<div id="publishing-action">
					<?php 
					if ($action == 'view') {
						if (!empty($referer)) {
							echo '<input class="hide-if-no-js" type="button" class="button-primary" onClick="document.location.href='."'".$referer."'".'" value="'.__('Back', self::$plugin_textdom).'" />';
						}
					} elseif ($evt->state == 'publish') {
						echo '<input id="save" class="button-primary" type="submit" value="'.__('Save', self::$plugin_textdom).'" name="save" />';
					} elseif ( $evt->userCanPublishEvent() ) {
						echo '<input id="publish" class="button-primary" type="submit" value="'.__('Publish', self::$plugin_textdom).'" name="publish" />';
					} elseif ( $evt->eventid > 0 ) {
						echo '<input id="save" class="button-primary" type="submit" value="'.__('Save', self::$plugin_textdom).'" name="save" />';
					} else {
						echo '<input id="save" class="button-primary" type="submit" value="'.__('Save Draft', self::$plugin_textdom).'" name="save" />';
					}
					?>
				</div>
				<div class="clear"></div>
			</div>
		</div>
		
		<?php echo $this->pagePostBoxEnd(); ?>
		
		<?php echo $this->pagePostBoxStart('date', __('When', self::$plugin_textdom)); ?>
			<table class="fs-table">
				<tbody>
					<tr>
						<th scope="row"><?php _e('From', self::$plugin_textdom); ?></th>
						<td>
							<input type="text"
						    	id="fse_datepicker_from<?php echo ($action=='view' ? 'dmy"' : ''); ?>" 
						    	name="event_from" 
						    	size="10"
						    	value="<?php echo $evt->date_admin_from; ?>" 
						    	<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
						    <input type="text"
						    	id="time_from"
						    	name="event_tfrom"
						    	size="5" 
						    	value="<?php echo $evt->time_admin_from; ?>" 
						    	<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php _e('To', self::$plugin_textdom); ?></th>
						<td>
							<input type="text"
						    	id="fse_datepicker_to<?php echo ($action=='view' ? 'dmy"' : ''); ?>" 
						    	name="event_to" 
						    	size="10"
						    	value="<?php echo $evt->date_admin_to; ?>" 
						    	<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
						    <input type="text"
						    	id="time_to"
						    	name="event_tto"
						    	size="5"
						    	value="<?php echo $evt->time_admin_to; ?>" 
						    	<?php echo ($action=='view' ? 'disabled="disabled"' : ''); ?>/>
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
						    <label for="allday"><?php _e('All day event', self::$plugin_textdom)?></label>
						</td>
					</tr>
				</tbody>
			</table>
		
		<?php echo $this->pagePostBoxEnd();	?>
		
		<?php echo $this->pagePostBoxStart('categorydiv', __('Categories', self::$plugin_textdom)); ?>
		<?php $this->postBoxCategories($evt->categories, ($action == 'view' ? true : false)); ?>		
		<?php echo $this->pagePostBoxEnd();	?>
		</div>
	</div>
	<div id="post-body">
		<div id="post-body-content">
			<p>
			<?php _e('Subject', self::$plugin_textdom); ?>
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
			<?php _e('Location', self::$plugin_textdom); ?>
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
	jQuery('#fse_datepicker_from').datepicker({dateFormat: '<?php echo $f; ?>'});
	jQuery('#fse_datepicker_to').datepicker({dateFormat: '<?php echo $f; ?>'});
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