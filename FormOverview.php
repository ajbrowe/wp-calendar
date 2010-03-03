<?php 
if ( !defined('ABSPATH') )
	die('-1');

// Base Link
$bl = 'admin.php?page='.self::$plugin_filename;

$event_actions = array('delete','publish','draft');

// If any action is defined, get id and validate
if (isset($_GET['action'])) {
	$errors = array();
	if (in_array($_GET['action'], $event_actions)) {
		if (!empty($_GET['event'])) {
			
			$e = new fsEvent(intval($_GET['event']));
			
			if (empty($e->eventid)) {
				$errors[] = sprintf(__('The event %d does not exist', self::$plugin_textdom), $e->eventid);
			}
		}
	}
	
	if (count($errors) == 0) {
		$act1 = $_GET['action'];
		$act2 = $_GET['action2'];
		if (!empty($act1))
			$act = $act1;
		elseif (!empty($act2))
			$act = $act2;
		else
			$act = '';
		switch($act) {
			case 'delete':
				if (isset($_GET['events'])) {
					$del_s = 0;
					$del_e = 0;
					foreach($_GET['events'] as $e) {
						$id = intval($e);
						$e = new fsEvent($id);
						
						if (empty($e->eventid))
							continue;
						
						if ($e->userCanDeleteEvent()) {
							$sql = 'DELETE FROM '.$wpdb->prefix.'fsevents '.' WHERE eventid='.$id;
							if ($wpdb->query($sql) === false) {
								$del_e++;
							} else {
								$del_s++;
							}
						} else {
							$del_e++;
						}
					}
					if (!empty($del_s) && empty($del_e)) {
						$success[] = __ngettext('Event successfully deleted', 'Events successfully deleted', $del_s, self::$plugin_textdom);
					} elseif (empty($del_s) && !empty($del_e)) {
						$errors[] = __ngettext('No permission to delete event', 'No permission to delete events', $del_e, self::$plugin_textdom);
					} elseif (!empty($del_s) && !empty($del_e)) {
						$errors[] = __('Some events could not be deleted because of missing permissions', self::$plugin_textdom);
					}
				} else {
					if ($e->userCanDeleteEvent()) {
						$sql = 'DELETE FROM '.$wpdb->prefix.'fsevents '.' WHERE eventid='.$e->eventid;
						$wpdb->query($sql);
						$success[] = __('Event successfully deleted', self::$plugin_textdom);
					} else {
						$errors[] = __('No permission to delete event', self::$plugin_textdom);
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
							if ($act == 'publish')
								$sql = 'UPDATE '.$wpdb->prefix.'fsevents '.' 
										SET state="publish", publishauthor='.intval($user_ID).', publishdate='.time().' 
										WHERE eventid='.$id;
							else
								$sql = 'UPDATE '.$wpdb->prefix.'fsevents '.' 
										SET state="draft", publishauthor=NULL, publishdate=NULL  
										WHERE eventid='.$id;
							
							$ret = $wpdb->query($sql);
								
							if ($ret === false) {
								$c_e++;
							} elseif ($ret > 0) {
								$c_s++;
							}
						} else {
							$c_e++;
						}
					}
					if (!empty($c_s) && empty($c_e)) {
						$success[] = __ngettext('Event successfully changed', 'Events successfully changed', $c_s, self::$plugin_textdom);
					} elseif (empty($c_s) && !empty($c_e)) {
						$errors[] = __ngettext('No permission to change event', 'No permission to change events', $c_e, self::$plugin_textdom);
					} elseif (!empty($c_s) && !empty($c_e)) {
						$errors[] = __('Some events could not be changed because of missing permissions', self::$plugin_textdom);
					}
				} 
				break;
		}
	}
}

echo $this->pageStart(__('Edit Events', self::$plugin_textdom), '', 'icon-edit');

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

$filter_stat = $_GET['event_status'];
if (isset(self::$valid_states[$filter_stat])) {
	$filter['state'] = $filter_stat;
	$link_actions = 'event_status='.$filter['state'].'&amp;';
}

$filter_author = intval($_GET['event_author']);
$user = new WP_User($filter_author);
if (!empty($user->data->ID)) {
	$filter['author'] = $filter_author;
	$link_actions = 'event_author='.$filter['author'].'&amp;';
}

$filter_category = intval($_GET['event_category']);
if (!empty($filter_category)) {
	$filter['categories'] = array($filter_category);
	$link_actions = 'event_category='.$filter_category.'&amp;';
}
	
$filter_date = intval($_GET['event_start']);
if ($filter_date > 0) {
	$m = date('m', $filter_date);
	$y = date('Y', $filter_date);
	$filter['datefrom'] = mktime(0, 0, 0, $m, 1, $y);
	$filter['dateto']   = mktime(0, 0, 0, ($m+1), 1, $y) - 1;
	$link_actions = 'event_start='.$filter_date.'&amp;';
}

// Create Link for transporting filter actions!
$bl_filter = $bl.'&amp;'.$link_actions;

// Count Events
$event_count = $this->getEvents($filter, '', 0, 0, true);

// Get Events per Page
$epp = get_option('fse_epp');

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

$events = $this->getEvents($filter, '', $epp, $start);
?>

<ul class="subsubsub">
<?php 
$count = $wpdb->get_var("SELECT COUNT(eventid) FROM ".$wpdb->prefix.'fsevents ');
echo '<li><a '.(!isset($filter['state']) ? 'class="current"' : '').' href="'.$bl.'">'.__('All', self::$plugin_textdom).'<span class="count"> ('.$count.')</span></a></li>';
foreach(self::$valid_states as $k => $l) {
	$count = $wpdb->get_var("SELECT COUNT(eventid) FROM ".$wpdb->prefix.'fsevents '." WHERE state='$k'");
	if ($count !== false && $count > 0)
		echo '<li>| <a '.($k == $filter['state'] ? 'class="current"' : '').' href="'.$bl.'&amp;event_status='.$k.'">'.$l.'<span class="count"> ('.$count.')</span></a></li>';
}
?>
</ul>

<form id="post" method="get" action="" name="event">
<input type="hidden" name="page" value="<?php echo self::$plugin_filename; ?>" />

<?php $this->printNavigationBar($filter, 1, $page, $epp, $event_count, $bl_filter); ?>

<table class="widefat post fixed" cellspacing="0">
	<thead>
		<tr>
			<th id="cb" class="manage-column column-cb check-column" style="" scope="col">
				<input type="checkbox" />
			</th>
			<th id="subject" class="manage-column" scope="col"><?php _e('Subject', self::$plugin_textdom);?></th>
			<th id="author" class="manage-column" scope="col"><?php _e('Author', self::$plugin_textdom);?></th>
			<th id="from" class="manage-column" scope="col"><?php _e('Date', self::$plugin_textdom);?></th>
			<th id="to" class="manage-column" scope="col"><?php _e('Time', self::$plugin_textdom);?></th>
			<th id="location" class="manage-column" scope="col"><?php _e('Location', self::$plugin_textdom);?></th>
			<th id="categories" class="manage-column" scope="col"><?php _e('Categories', self::$plugin_textdom);?></th>
			<th id="date" class="manage-column" scope="col"><?php _e('State', self::$plugin_textdom);?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th class="manage-column column-cb check-column" scope="col">
				<input type="checkbox" />
			</th>
			<th class="manage-column" scope="col"><?php _e('Subject', self::$plugin_textdom);?></th>
			<th class="manage-column" scope="col"><?php _e('Author', self::$plugin_textdom);?></th>
			<th class="manage-column" scope="col"><?php _e('Date', self::$plugin_textdom);?></th>
			<th class="manage-column" scope="col"><?php _e('Time', self::$plugin_textdom);?></th>
			<th class="manage-column" scope="col"><?php _e('Location', self::$plugin_textdom);?></th>
			<th class="manage-column" scope="col"><?php _e('Categories', self::$plugin_textdom);?></th>
			<th class="manage-column" scope="col"><?php _e('State', self::$plugin_textdom);?></th>
		</tr>
	</tfoot>
	<tbody>
		<?php
		if (!is_array($events) || count($events) == 0) {
			?>
			<tr><td colspan="8"><?php _e('No events found', self::$plugin_textdom); ?></td></tr>
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
					<a class="row-title" 
						title="<?php _e('Edit', self::$plugin_textdom); ?> “<?php echo esc_attr($e->subject); ?>”" 
						href="<?php echo $bl; ?>&amp;action=<?php echo ($e->userCanEditEvent() == true ? 'edit' : 'view'); ?>&amp;event=<?php echo esc_attr($e->eventid); ?>">
					<?php echo esc_attr($e->subject); ?></a>
					<?php 
					switch($e->state) {
						case 'draft':
							echo ' - '.__('Draft', self::$plugin_textdom);
							break;	
					}
					?>
					</strong>
					<div class="row-actions">
					<span class="edit">
						<?php if ($e->userCanEditEvent()) { ?>
						<a title="<?php _e('Edit this event', self::$plugin_textdom); ?>" 
							href="<?php echo $bl; ?>&amp;action=edit&amp;event=<?php echo esc_attr($e->eventid); ?>"><?php _e('Edit', self::$plugin_textdom);?></a> |
						<?php } else { ?>
							<?php _e('Edit', self::$plugin_textdom);?> | 
						<?php } ?> 
					</span>
					<span class="delete">
						<?php if ($e->userCanDeleteEvent()) { ?>
						<a class="submitdelete" onclick="if ( confirm('<?php printf(__("You are about to delete this event \\'%s\\'\\n \\'Cancel\\' to stop, \\'OK\\' to delete.", self::$plugin_textdom), esc_attr($e->subject)); ?>') ) { return true;}return false;"
							href="<?php echo $bl; ?>&amp;action=delete&event=<?php echo esc_attr($e->eventid); ?>" 
							title="<?php _e('Delete this event', self::$plugin_textdom); ?>"><?php _e('Delete', self::$plugin_textdom);?></a> | 
						<?php } else { ?>
							<?php _e('Delete', self::$plugin_textdom);?> | 
						<?php } ?>
					</span>
					<span class="view">
						<a title="<?php _e('View this event', self::$plugin_textdom); ?>" 
							href="<?php echo $bl; ?>&amp;action=view&amp;event=<?php echo esc_attr($e->eventid); ?>"><?php _e('View', self::$plugin_textdom);?></a>
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
						_e('All day event', self::$plugin_textdom);
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
				<td><?php echo esc_attr(self::$valid_states[$e->state]); ?> <?php _e('on', self::$plugin_textdom) ?><br />
				<?php echo date('d.m.Y H:i:s', ($e->state == 'publish' ? $e->publishdate : $e->createdate)); ?><br /></td>
			</tr>
			<?php
			}
		}
		?>
	</tbody>
</table>
<?php $this->printNavigationBar($filter, 2, $page, $epp, $event_count, $bl_filter); ?>
<p><input type="button" class="button-primary" name="back" value="<?php _e('Add New Event', self::$plugin_textdom); ?>" onClick="document.location.href='<?php echo $bl; ?>&amp;action=new';" /></p>
</form>
<?php
echo $this->pageEnd();
?>