<?php 
global $wpdb;

if ( !defined('ABSPATH') )
	die('-1');

// Base Link
$bl = 'admin.php?page='.fsCalendar::$plugin_filename;
$bl_new = 'admin.php?page=wp-cal-add';

$event_actions = array('delete','publish','draft');

$fatal = $errors = $success = array();

// If any action is defined, get id and validate
if (isset($_GET['action'])) {
	$errors = array();
	if (in_array($_GET['action'], $event_actions)) {
		if (!empty($_GET['event'])) {
			
			$e = new fsEvent(intval($_GET['event']));
			
			if (empty($e->eventid)) {
				$errors[] = sprintf(__('The event %d does not exist', fsCalendar::$plugin_textdom), $e->eventid);
			}
		}
	}
	
	if (count($errors) == 0) {
		$act1 = (isset($_GET['action']) ? $_GET['action'] : '');
		$act2 = (isset($_GET['action2'])? $_GET['action2'] : '');
		if (!empty($act1))
			$act = $act1;
		elseif (!empty($act2))
			$act = $act2;
		else
			$act = '';
		switch($act) {
			case 'delete':
				if (isset($_GET['event'])) {
					$_GET['events'][] = $_GET['event'];
				}
				if (isset($_GET['events'])) {
					$del_s = 0;
					$del_e = 0;
					foreach($_GET['events'] as $e) {
						$id = intval($e);
						$e = new fsEvent($id);
						
						if (empty($e->eventid))
							continue;
						
						if ($e->userCanDeleteEvent()) {
							if ($e->delete()) {
								$del_s++;
							} else {
								$del_e++;
							}
						} else {
							$del_e++;
						}
					}
					if (!empty($del_s) && empty($del_e)) {
						$success[] = __ngettext('Event successfully deleted', 'Events successfully deleted', $del_s, fsCalendar::$plugin_textdom);
					} elseif (empty($del_s) && !empty($del_e)) {
						$errors[] = __ngettext('No permission to delete event', 'No permission to delete events', $del_e, fsCalendar::$plugin_textdom);
					} elseif (!empty($del_s) && !empty($del_e)) {
						$errors[] = __('Some events could not be deleted because of missing permissions', fsCalendar::$plugin_textdom);
					}
				}
				break;
			case 'publish':
			case 'draft':
				if (isset($_GET['events'])) {
					$c_s = 0;
					$c_e = 0;
					foreach($_GET['events'] as $e) {
						$id = intval($e);
						$e = new fsEvent($id);
						
						if (empty($e->eventid))
							continue;
						
						if ($e->userCanEditEvent()) {
							if ($act == 'publish') {
								$ret = $e->setStatePublished();
							} else {
								$ret = $e->setStateDraft();
							}
							
							$ret = $wpdb->query($sql);
								
							if ($ret === true) {
								$c_s++;
							} else {
								$c_e++;
							}
						} else {
							$c_e++;
						}
					}
					if (!empty($c_s) && empty($c_e)) {
						$success[] = __ngettext('Event successfully changed', 'Events successfully changed', $c_s, fsCalendar::$plugin_textdom);
					} elseif (empty($c_s) && !empty($c_e)) {
						$errors[] = __ngettext('No permission to change event', 'No permission to change events', $c_e, fsCalendar::$plugin_textdom);
					} elseif (!empty($c_s) && !empty($c_e)) {
						$errors[] = __('Some events could not be changed because of missing permissions', fsCalendar::$plugin_textdom);
					}
				} 
				break;
		}
	}
}

echo $this->pageStart(__('Edit Events', fsCalendar::$plugin_textdom), '', 'icon-edit');

// Check DB Version
$dbver = get_option('fse_db_version', -1);
if ($dbver < FSE_DB_VERSION) {
	
} else {
	// Bei Fatal Errors gleich wieder raus!
	if (count($fatal) > 0) {
		echo '<div id="notice" class="error"><p>';
		foreach($fatal as $f) {
			echo $f.'</br>';
		}
		echo '</p></div>';
		echo $this->pageEnd();
		return;	
	}
	if (count($errors) > 0) {
		echo '<div id="notice" class="error"><p>';
		foreach($errors as $e) {
			echo $e.'</br>';
		}
		echo '</p></div>';
	}
	if (count($success) > 0) {
		echo '<div id="message" class="updated fade"><p>';
		foreach($success as $e) {
			echo $e.'</br>';
		}
		echo '</p></div>';
	}
	
	$filter = array();
	
	$link_actions = '';
	
	$filter_stat = (isset($_GET['event_status']) ? $_GET['event_status'] : '');
	if (isset(fsCalendar::$valid_states[$filter_stat])) {
		$filter['state'] = $filter_stat;
		$link_actions .= 'event_status='.$filter['state'].'&amp;';
	}
	
	$filter_author = (isset($_GET['event_author']) ? intval($_GET['event_author']) : 0);
	$user = new WP_User($filter_author);
	if (!empty($user->data->ID)) {
		$filter['author'] = $filter_author;
		$link_actions .= 'event_author='.$filter['author'].'&amp;';
	}
	
	$filter_category = (isset($_GET['event_category']) ? intval($_GET['event_category']) : 0);
	if (!empty($filter_category)) {
		$filter['categories'] = array($filter_category);
		$link_actions .= 'event_category='.$filter_category.'&amp;';
	}
		
	$filter_date = (isset($_GET['event_start']) ? $_GET['event_start'] : 'future'); // 0 -> Future dates only!
	if ($filter_date > 0) {
		$m = mysql2date('m', $filter_date);
		$y = mysql2date('Y', $filter_date);
		$filter['datefrom'] = date('Y-m-d H:i:s', mktime(0, 0, 0, $m, 1, $y));
		$filter['dateto']   = date('Y-m-d H:i:s', mktime(0, 0, 0, ($m+1), 1, $y) - 1);
	// Only Future dates
	} elseif ($filter_date == 'future') {
		$filter['datefrom'] = 'now';
		//$filter['datemode'] = FSE_DATE_MODE_END;
		//$filter['dateto']   = mktime(0, 0, 0, ($m+1), 1, $y) - 1;
	} elseif ($filter_date == 'all') {
		// No time filter
	}
	$link_actions .= 'event_start='.$filter_date.'&amp;';
	
	$sort = (isset($_GET['event_sort']) ? $_GET['event_sort'] : '');
	$sortstring = '';
	if (in_array($sort, array('subject', 'author', 'datefrom', 'location'))) {
		$sortstring = '`'.$sort.'`';
		$sortdir = $_GET['event_sortdir'];
		if (in_array($sortdir, array('ASC', 'DESC'))) {
			$sortstring .= ' '.$sortdir;
		} else {
			$sortdir = 'ASC';
		}
	} else {
		$sort = 'datefrom';
		$sortdir = 'DESC';
		
		$sortstring = '`datefrom` DESC';
	}
	
	// Create Link for transporting filter actions!
	$bl_filter = $bl.'&amp;'.$link_actions;
	
	// Count Events (of different kind of states
	$event_count = $fsCalendar->getEvents($filter, '', 0, 0, true);
	
	$filter_count = $filter;
	
	unset($filter_count['state']);
	$event_count_total = $fsCalendar->getEvents($filter_count, '', 0, 0, true);
	foreach(fsCalendar::$valid_states as $k => $l) {
		$filter_count['state'] = $k;
		$state_count[$k] = $fsCalendar->getEvents($filter_count, '', 0, 0, true);
	}
	
	// If for the current state no events are selected, reset to all
	if (isset($filter['state'])) {
		if (!isset($state_count[$filter['state']]) || $state_count[$filter['state']] <= 0) {
			unset($filter['state']);
		}
	}
	
	// Get Events per Page
	$epp = 20;
	
	if ($event_count > $epp) {
		if (isset($_GET['paged'])) {
			$page = intval($_GET['paged']);
		} else {
			$page = 1;
		}
		
		$limit = $epp;
		$start = ($page - 1) * $epp;
	} else {
		$limit = $start = $page = 0;
	}
	
	$events = $fsCalendar->getEvents($filter, $sortstring, $epp, $start);
	?>
	
	<ul class="subsubsub">
	<?php 
	//$count = $wpdb->get_var("SELECT COUNT(eventid) FROM ".$wpdb->prefix.'fsevents ');
	echo '<li><a '.(!isset($filter['state']) ? 'class="current"' : '').' href="'.$bl.'">'.__('All', fsCalendar::$plugin_textdom).'<span class="count"> ('.$event_count_total.')</span></a></li>';
	foreach(fsCalendar::$valid_states as $k => $l) {
		//$count = $wpdb->get_var("SELECT COUNT(eventid) FROM ".$wpdb->prefix.'fsevents '." WHERE state='$k'");
		//if ($count !== false && $count > 0)
		if (isset($state_count[$k]) && $state_count[$k] > 0)
			echo '<li>| <a '.((isset($filter['state']) && $k == $filter['state']) ? 'class="current"' : '').' href="javascript: fse_overviewFilter('."'event_status','$k'".');">'.$l.'<span class="count"> ('.$state_count[$k].')</span></a></li>';
	}
	?>
	</ul>
	
	<form id="event" method="get" action="" name="event">
	
	<input type="hidden" name="event_status" value="<?php echo (isset($filter['state']) ? $filter['state'] : ''); ?>" />
	<input type="hidden" name="event_sort" value="<?php echo (isset($sort) ? $sort : ''); ?>" />
	<input type="hidden" name="event_sortdir" value="<?php echo (isset($sortdir) ? $sortdir : ''); ?>" />
	
	<input type="hidden" name="page" value="<?php echo fsCalendar::$plugin_filename; ?>" />
	
	
	<?php $this->printNavigationBar($filter, 1, $page, $epp, $event_count, $bl_filter); ?>
	
	<table class="widefat post fixed" cellspacing="0">
		<thead>
			<tr>
				<th id="cb" class="manage-column column-cb check-column" style="" scope="col">
					<input type="checkbox" />
				</th>
				<th id="subject" class="manage-column" scope="col"><a href="javascript: fse_overviewSort('subject');">
					<?php _e('Subject', fsCalendar::$plugin_textdom);?></a>
					<?php if ($sort == 'subject') { echo '<img src="'.fsCalendar::$plugin_img_url.'sort'.$sortdir.'.png" alt="" />'; } ?></th>
				<th id="author" class="manage-column" scope="col"><a href="javascript: fse_overviewSort('author');">
					<?php _e('Author', fsCalendar::$plugin_textdom);?></a>
					<?php if ($sort == 'author') { echo '<img src="'.fsCalendar::$plugin_img_url.'sort'.$sortdir.'.png" alt="" />'; } ?></th>
				<th id="datefrom" class="manage-column" scope="col"><a href="javascript: fse_overviewSort('datefrom');">
					<?php _e('Date', fsCalendar::$plugin_textdom);?></a>
					<?php if ($sort == 'datefrom') { echo '<img src="'.fsCalendar::$plugin_img_url.'sort'.$sortdir.'.png" alt="" />'; } ?></th>
				<th id="to" class="manage-column" scope="col"><?php _e('Time', fsCalendar::$plugin_textdom);?></th>
				<th id="location" class="manage-column" scope="col"><a href="javascript: fse_overviewSort('location');">
					<?php _e('Location', fsCalendar::$plugin_textdom);?></a>
					<?php if ($sort == 'location') { echo '<img src="'.fsCalendar::$plugin_img_url.'sort'.$sortdir.'.png" alt="" />'; } ?></th>
				<th id="categories" class="manage-column" scope="col"><?php _e('Categories', fsCalendar::$plugin_textdom);?></th>
				<th id="comments" class="manage-column num" scope="col" style="width: 60px">
					<img src="images/comment-grey-bubble.png" alt="" />
				</th>
				<th id="date" class="manage-column" scope="col"><?php _e('State', fsCalendar::$plugin_textdom);?></th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<th class="manage-column column-cb check-column" scope="col">
					<input type="checkbox" />
				</th>
				<th class="manage-column" scope="col"><?php _e('Subject', fsCalendar::$plugin_textdom);?></th>
				<th class="manage-column" scope="col"><?php _e('Author', fsCalendar::$plugin_textdom);?></th>
				<th class="manage-column" scope="col"><?php _e('Date', fsCalendar::$plugin_textdom);?></th>
				<th class="manage-column" scope="col"><?php _e('Time', fsCalendar::$plugin_textdom);?></th>
				<th class="manage-column" scope="col"><?php _e('Location', fsCalendar::$plugin_textdom);?></th>
				<th class="manage-column" scope="col"><?php _e('Categories', fsCalendar::$plugin_textdom);?></th>
				<th class="manage-column num" scope="col">
					<img src="images/comment-grey-bubble.png" alt="" />
				</th>
				<th class="manage-column" scope="col"><?php _e('State', fsCalendar::$plugin_textdom);?></th>
			</tr>
		</tfoot>
		<tbody>
			<?php
			if (!is_array($events) || count($events) == 0) {
				?>
				<tr><td colspan="8"><?php _e('No events found', fsCalendar::$plugin_textdom); ?></td></tr>
				<?php 
			} else { 
				foreach($events as $e) { 
					$e = new fsEvent($e);
				?>
				<tr id="event-<?php echo $e->eventid; ?>" class="alternate status-<?php echo esc_attr($e->state); ?> iedit" valign="top">
					<th class="check-column" scope="row">
						<input type="checkbox" value="<?php echo esc_attr($e->eventid); ?>" name="events[]"/>
					</th>
					<td>
						<strong>
						<?php 
						if ($e->updatedbypost == true) {
							echo '<img src="'.fsCalendar::$plugin_img_url.'synchronized.png" alt="" />';
						}
						?>
						<a class="row-title" 
							title="<?php _e('Edit', fsCalendar::$plugin_textdom); ?> <?php echo esc_attr($e->subject); ?>" 
							href="<?php echo $bl; ?>&amp;action=<?php echo ($e->userCanEditEvent() == true ? 'edit' : 'view'); ?>&amp;event=<?php echo esc_attr($e->eventid); ?>">
						<?php echo esc_attr($e->subject); ?></a>
						<?php 
						switch($e->state) {
							case 'draft':
								echo ' - '.__('Draft', fsCalendar::$plugin_textdom);
								break;	
						}
						?>
						</strong>
						<div class="row-actions">
						<span class="edit">
							<?php if ($e->userCanEditEvent()) { ?>
							<a title="<?php _e('Edit this event', fsCalendar::$plugin_textdom); ?>" 
								href="<?php echo $bl; ?>&amp;action=edit&amp;event=<?php echo esc_attr($e->eventid); ?>"><?php _e('Edit', fsCalendar::$plugin_textdom);?></a> |
							<?php } else { ?>
								<?php _e('Edit', fsCalendar::$plugin_textdom);?> | 
							<?php } ?> 
						</span>
						<?php if (!empty($e->postid)) { ?>
						<span class="editpost">
							<?php if ($e->userCanEditEvent()) { ?>
							<a title="<?php _e('Edit post', fsCalendar::$plugin_textdom); ?>" 
								href="<?php echo get_edit_post_link($e->postid); ?>"><?php _e('Edit Post', fsCalendar::$plugin_textdom);?></a> |
							<?php } else { ?>
								<?php _e('Edit Post', fsCalendar::$plugin_textdom);?> | 
							<?php } ?> 
						</span>
						<?php } ?>
						<span class="duplicate">
							<?php if ($fsCalendar->userCanAddEvents()) { ?>
							<a title="<?php _e('Duplicate this event', fsCalendar::$plugin_textdom); ?>" 
								href="<?php echo $bl; ?>&amp;action=copy&amp;event=<?php echo esc_attr($e->eventid); ?>"><?php _e('Duplicate', fsCalendar::$plugin_textdom);?></a> |
							<?php } else { ?>
								<?php _e('Duplicate', fsCalendar::$plugin_textdom);?> | 
							<?php } ?> 
						</span>
						<span class="delete">
							<?php if ($e->userCanDeleteEvent()) { ?>
							<a class="submitdelete" onclick="if ( confirm('<?php printf(__("You are about to delete this event \\'%s\\'\\n \\'Cancel\\' to stop, \\'OK\\' to delete.", fsCalendar::$plugin_textdom), esc_attr($e->subject)); ?>') ) { return true;}return false;"
								href="<?php echo $bl; ?>&amp;action=delete&amp;event=<?php echo esc_attr($e->eventid); ?>" 
								title="<?php _e('Delete this event', fsCalendar::$plugin_textdom); ?>"><?php _e('Delete', fsCalendar::$plugin_textdom);?></a> |
							<?php } else { ?>
								<?php _e('Delete', fsCalendar::$plugin_textdom);?> |
							<?php } ?>
						</span>
						<span class="view">
							<a title="<?php _e('View this event', fsCalendar::$plugin_textdom); ?>" 
								href="<?php echo $bl; ?>&amp;action=view&amp;event=<?php echo esc_attr($e->eventid); ?>"><?php _e('View', fsCalendar::$plugin_textdom);?></a>
						</span>
						</div>
					</td>
					<td>
						<?php 
						echo '<a href="'.$bl.'&amp;event_author='.esc_attr($e->author).'">'.esc_attr($e->author_t).'</a>';
						?>
					</td>
					<td>
						<?php
						$df = $e->getStart('', 2);
						$dt = $e->getEnd('', 2);
						echo $df;
						if ($dt != $df) {
							echo '<br />'.$dt;
						}
						?>
					</td>
					<td>
						<?php 
						if ($e->allday == true) {
							_e('All day event', fsCalendar::$plugin_textdom);
						} else {
							echo $e->getStart('', 3).'<br />'.$e->getEnd('', 3);
						}
						?>
					</td>
					<td><?php echo format_to_post($e->location); ?></td>
					<td><?php
					$first = true;
					foreach($e->categories_t as $k => $c) {
						if ($first == false) {
							echo ', ';
						} else {
							$first = false;	
						}
						echo '<a href="'.$bl.'&amp;event_category='.esc_attr($k).'">'.esc_attr($c).'</a>';
						
					}
					?></td>
					<td class="num">
					<?php
					$count = $wpdb->get_var('SELECT COUNT(*) FROM '.$wpdb->commentmeta.' WHERE meta_key="event_id" AND meta_value="'.$e->eventid.'"');
					
					echo '<span class="post-com-count" style="cursor: default"><span class="comment-count" style="cursor: default">'.$count.'</span></span>';
					
					?>
					</td>
					<td><?php echo esc_attr(fsCalendar::$valid_states[$e->state]); ?> <?php _e('on', fsCalendar::$plugin_textdom) ?><br />
					<?php echo mysql2date('d.m.Y H:i:s', ($e->state == 'publish' ? $e->publishdate : $e->createdate)); ?><br /></td>
				</tr>
				<?php
				}
			}
			?>
		</tbody>
	</table>
	<?php $this->printNavigationBar($filter, 2, $page, $epp, $event_count, $bl_filter); ?>
	<?php if ($fsCalendar->userCanAddEvents()) { ?>
		<p><input type="button" class="button-primary" name="back" value="<?php _e('Add New Event', fsCalendar::$plugin_textdom); ?>" onclick="document.location.href='<?php echo $bl_new; ?>';" /></p>
	<?php } ?>
	</form>
<?php
}
echo $this->pageEnd();
?>