<?php
class fsEvent {
	var $eventid = 0;
	var $postid = 0;
	var $subject;
	var $location; 
	var $description;
	var $from;
	var $to;
	var $allday;
	var $author;
	var $createdate;
	var $publishauthor;
	var $publishdate;
	var $categories = array();
	var $state;
	var $updatedbypost;
	
	// For Admin only
	var $date_admin_from;
	var $date_admin_to;
	var $time_admin_from;
	var $time_admin_to;
	
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
	
	function fsEvent($eventid = 0, $state = '', $admin_fields = true, $postid = 0) {
		global $wpdb;
		
		$this->loadOptions($admin_fields);
			
		$this->eventid = intval($eventid);
		$this->postid  = intval($postid);
				
		if (empty($this->eventid) && empty($this->postid)) {
			return;
		}
		
		// If post ID is provided just lookup and get eventid
		if (empty($this->eventid) && !empty($this->postid)) {
			$sql = $wpdb->prepare('SELECT eventid FROM '.$wpdb->prefix.'fsevents '.' WHERE postid=%d', $this->postid);
			$this->eventid = $wpdb->get_var($sql);
			if (empty($this->eventid))
				return;
		}
		
		if (!empty($state))
			$sql = $wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'fsevents '.' WHERE eventid='.$this->eventid.' AND state=%s', $state);
		else
			$sql = 'SELECT * FROM '.$wpdb->prefix.'fsevents '.' WHERE eventid='.$this->eventid;
		
		if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
			echo '<p>'.$sql.'</p>';
		}
			
		$ret = $wpdb->get_row($sql, OBJECT);
		
		if ($ret == NULL) {
			$evt->eventid = 0;
			return;
		}
		
		$this->postid = $ret->postid;
		$this->updatedbypost = ($ret->updatedbypost == true ? true : false);
		$this->subject = $ret->subject;
		$this->location = $ret->location;
		$this->description = $ret->description;
		
		$this->from = $ret->datefrom;
		$this->to = $ret->dateto;
		
		$this->allday = ($ret->allday == true ? true : false);
		$this->author = $ret->author;
		$this->publishauthor = $ret->publishauthor;
		$this->createdate = $ret->createdaten;
		$this->publishdate = $ret->publishdaten;
		$this->state = $ret->state;
		
		$sql = 'SELECT catid FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$this->eventid;
		if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
			echo '<p>'.$sql.'</p>';
		}
			
		$this->categories = $wpdb->get_col($sql);
		
		foreach($this as $k => $v) {
			if (is_string($v)) {
				$this->{$k} = stripslashes($v);
			}
		}
		
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
			$this->date_admin_from = mysql2date($this->date_admin_format, $this->from);
			$this->date_admin_to   = mysql2date($this->date_admin_format, $this->to);
			$this->time_admin_from = mysql2date($this->time_admin_format, $this->from);
			$this->time_admin_to   = mysql2date($this->time_admin_format, $this->to);
		}
	}	

	function loadOptions($admin_fields = true) {
		// Load options
		if (get_option('fse_df_wp') == true)
			$this->date_format = get_option('date_format');
		else
			$this->date_format = get_option('fse_df');
		
		if (get_option('fse_tf_wp') == true)
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
		if (empty($this->from) || $this->from == '0000-00-00 00:00:00')
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
		
		return mysql2date($fmt, $this->from);
	}
	
	/**
	 * Returns the formatted end date/time string
	 * @param $fmt Format (See PHP function date())
	 * @param $mode Mode (1=Date+Time, 2=Date only, 3=Time only)
	 * @return String Formatted date string
	 */
	function getEnd($fmt = '', $mode = 1) {
		if (empty($this->to) || $this->to == '0000-00-00 00:00:00')
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
		
		return mysql2date($fmt, $this->to);
	}
	
	function getDescription($truncate_more = false) {
		$desc = $this->description;
		if ($truncate_more == true) {
			list($desc, $dummy) = explode('<!--more-->', $this->description, 2);
		}
		return apply_filters('the_content', $desc);
	}
	
	function userCanPublishEvent() {
		
		if (empty($this->eventid))
			return true;
		
		$ret = $this->userCanEditEvent();
		if ($ret == false)
			return false;
		else
			return current_user_can('publish_posts');	
	}
	
	function userCanViewEvent() {
		return current_user_can('read');
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
			return current_user_can('edit_others_posts');	
		// Edit of published only by editor!
		} elseif ($this->state == 'publish') {
			return current_user_can('edit_published_posts');
		} else {
			return current_user_can('edit_posts');	
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
			return current_user_can('delete_others_posts');	
		} elseif ($this->state == 'publish') {
			return current_user_can('delete_published_posts');
		} else {
			return current_user_can('delete_posts');	
		}
	}
	
	/**
	 * Saves the current event back to the database
	 * This returns true, if successfull or an array with error messages
	 * @use To check the result compare it with true using === (3!)
	 * @return True, if successfull or an array of error messages
	 */
	function saveToDataBase() {
		global $wpdb;
		global $user_ID;
		global $fsCalendar;
		
		$errors = array();
		
		if ($this->eventid <= 0 && !$fsCalendar->userCanAddEvents()) {
			return __('No permission to create event', fsCalendar::$plugin_textdom);
		}
		if ($this->eventid > 0 && !$this->userCanEditEvent()) {
			return __('No permission to edit event', fsCalendar::$plugin_textdom);
		}
		
		// Do all the validaten
		if (!is_array($this->categories)) {
			$this->categories = array(1); // Uncategorized
		}
		
		// Vaidate subject
		if (empty($this->subject)) {
			$errors[] = __('Please enter a subject', fsCalendar::$plugin_textdom);
		}
		// Validate date/time
		$ret_df = fse_ValidateDate($this->date_admin_from, $this->date_admin_format);
		if ($ret_df === false) {
			$errors[] = __('Please enter a valid `from` date', fsCalendar::$plugin_textdom);
		} else {
			$this->date_admin_from = $ret_df;
		}
		if ($this->allday == 0) {
			$ret_tf = fse_ValidateTime($this->time_admin_from);
			if ($ret_tf === false) {
				$errors[] = __('Please enter a valid `from` time', fsCalendar::$plugin_textdom);
			} else {
				$this->time_admin_from = $ret_tf;
			}
		} else {
			$this->time_admin_from = '00:00';
		}
		$ret_dt = fse_ValidateDate($this->date_admin_to, $this->date_admin_format);
		if ($ret_dt === false) {
			$errors[] = __('Please enter a valid `to` date', fsCalendar::$plugin_textdom);
		} else {
			$this->date_admin_to = $ret_dt;
		}
		if ($this->allday == 0) {
			$ret_tt = fse_ValidateTime($this->time_admin_to);
			if ($ret_tt === false) {
				$errors[] = __('Please enter a valid `to` time', fsCalendar::$plugin_textdom);
			} else {
				$this->time_admin_to = $ret_tt;
			}
		} else {
			$this->time_admin_to = '00:00';
		}
		
		$fd = fse_ValidateDate($this->date_admin_from, $this->date_admin_format, true);
		$ft = fse_ValidateTime($this->time_admin_from, true);
		$td = fse_ValidateDate($this->date_admin_to, $this->date_admin_format, true);
		$tt = fse_ValidateTime($this->time_admin_to, true);
		
		$from = date('Y-m-d H:i:s', mktime($ft['h'], $ft['m'], 0, $fd['m'], $fd['d'], $fd['y']));
		$to   = date('Y-m-d H:i:s', mktime($tt['h'], $tt['m'], 0, $td['m'], $td['d'], $td['y']));
				
		//$ts_from = mktime($ft['h'], $ft['m'], 0, $fd['m'], $fd['d'], $fd['y']);
		//$ts_to   = mktime($tt['h'], $tt['m'], 0, $td['m'], $td['d'], $td['y']);
		
		if (empty($this->state)) {
			$this->state = 'draft';
		}
		
		if ($from > $to) {
			$errors[] = __('End is before start', fsCalendar::$plugin_textdom);
		}
		
		// Error -> return them
		if (count($errors) > 0) {
			return $errors;
		}
							
		if ($this->eventid > 0) {
			// Check authority
			if ($this->userCanEditEvent()) {
				$sql = $wpdb->prepare("UPDATE ".$wpdb->prefix.'fsevents '."
					SET `subject`=%s, `datefrom`=%s, `dateto`=%s, `allday`=%d, `description`=%s, `location`=%s, `state`=%s, 
					`updatedbypost`=%d 
					WHERE `eventid`=$this->eventid",
		        	$this->subject, $from, $to, ($this->allday == true ? 1 : 0), $this->description, $this->location, $this->state, ($this->updatedbypost == true ? 1 : 0));
			} else {
				$errors[] = __('No permission to edit event', fsCalendar::$plugin_textdom);
			}
		} else {
			if ($fsCalendar->userCanAddEvents()) {
				$now = get_date_from_gmt(date('Y-m-d H:i:s'));
				
				if (empty($this->postid))
					$postid = 'NULL';
				else
					$postid = intval($this->postid);
				
					
				$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix.'fsevents '."
					(`subject`, `datefrom`, `dateto`, `allday`, `description`, `location`, `author`, `createdaten`, `state`, `postid`, `updatedbypost`)
					VALUES (%s, %s, %s, %d, %s, %s, $user_ID, %s, %s, $postid, %d)", 
		        	$this->subject, $from, $to, ($this->allday == true ? 1 : 0), $this->description, $this->location, $now, $this->state, ($this->updatedbypost == true ? 1 : 0));
			} else {
				$errors[] = __('No permission to create event', fsCalendar::$plugin_textdom);
			}
		}
        
		// Error -> return them
		if (count($errors) > 0) {
			return $errors;
		}
		
		if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
			echo '<p>'.$sql.'</p>';
		}
		
        if ($wpdb->query($sql) !== false) {
        	if ($this->eventid <= 0) {
	        	$this->eventid = $wpdb->insert_id;
	        	
	        	$this->author = $user_ID;
	        	$this->createdate = $now;
	        	
	        	$u = new WP_User($user_ID);
	        	$this->author_t = $u->display_name;
	        	unset($u);
	        	
	        	$action = 'edit'; // Switch to edit mode!
        	} else {
        		$success[] = __('Event updated', fsCalendar::$plugin_textdom);
        	}
        	
        	// Handle categories
        	$ret_cats = $wpdb->get_col('SELECT `catid` FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$this->eventid);
        	if (!is_array($ret_cats)) {
        		$ret_cats = array();
        	}
        	
        	// Insert missing
        	foreach($this->categories as $c) {
        		if (!in_array($c, $ret_cats)) {
        			$sql = 'INSERT INTO '.$wpdb->prefix.'fsevents_cats VALUES ('.$this->eventid.','.$c.')';
        			$wpdb->query($sql);
        		}
        	}
        	// Remove old
        	foreach($ret_cats as $c) {
        		if (!in_array($c, $this->categories)) {
        			$sql = 'DELETE FROM '.$wpdb->prefix.'fsevents_cats WHERE `eventid`='.$this->eventid.' AND `catid`='.$c;
        			$wpdb->query($sql);
        		}
        	}
        	return true;
        	
        } else {
        	$errors[] = __('DB Error', fsCalendar::$plugin_textdom);
        	return $errors;
        }
	}
	
	/**
	 * Publishes the current event
	 * @use To check the result compare it with true using === (3!)
	 * @return True, if successfull or an error message
	 */
	function setStatePublished() {
		global $user_ID;
		global $wpdb;
		
		if (empty($this->eventid)) {
			return __('Event does not exist');
		}
		if ($this->eventid > 0 && !$this->userCanEditEvent()) {
			return __('No permission to edit event', fsCalendar::$plugin_textdom);
		}
		
		$publishdate = get_date_from_gmt(date('Y-m-d H:i:s'));
		
		$sql = 'UPDATE '.$wpdb->prefix.'fsevents '.' 
						  SET `state`="publish", `publishauthor`="'.intval($user_ID).'", `publishdaten`="'.$publishdate.'" 
						  WHERE `eventid`='.$this->eventid;
		if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
			echo '<p>'.$sql.'</p>';
		}
		
		if ($wpdb->query($sql) !== false) {
			$this->state = 'publish';
			$this->publishauthor = $user_ID;
			$this->publishdate   = $publishdate;
			$u = new WP_User($user_ID);
			$this->publishauthor_t = $u->display_name;
			unset($u);
			
			return true;			
		} else {
			return __('Event could not be published', fsCalendar::$plugin_textdom);
		}
	}
	
	/**
	 * Publishes the current event
	 * @use To check the result compare it with true using === (3!)
	 * @return True, if successfull or an error message
	 */
	function setStateDraft() {
		global $user_ID;
		global $wpdb;
		
		if (empty($this->eventid)) {
			return __('Event does not exist');
		}
		if ($this->eventid > 0 && !$this->userCanEditEvent()) {
			return __('No permission to edit event', fsCalendar::$plugin_textdom);
		}
		
		$sql = 'UPDATE '.$wpdb->prefix.'fsevents '.' 
					  SET `state`="draft", `publishdaten`=NULL, `publishauthor`=NULL 
					  WHERE `eventid`='.$this->eventid;
		
		if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
			echo '<p>'.$sql.'</p>';
		}
		
		if ($wpdb->query($sql) !== false) {
			$this->state = 'draft';
			$this->publishauthor = '';
			$this->publishauthor_t = '';
			$this->publishdate = '';
			return true;
		} else {
			return __('Event could not be set to draft state', fsCalendar::$plugin_textdom);
		}
	}
	
	/**
	 * Disabled Synchronization
	 */
	function disableSynchronization() {
		global $wpdb;
		if ($this->updatedbypost) {
			$sql = $wpdb->prepare("UPDATE ".$wpdb->prefix.'fsevents '."
				SET `updatedbypost`=0 
				WHERE `eventid`=$this->eventid");
			
			if (defined('FSE_SQL_DEBUG') && FSE_SQL_DEBUG == true && defined('WP_DEBUG') && WP_DEBUG == true) {
				echo '<p>'.$sql.'</p>';
			}
			
			$wpdb->query($sql);
			
			$this->updatedbypost = false;
		}
	}
	
	function delete() {
		global $wpdb;
		$sql = 'DELETE FROM '.$wpdb->prefix.'fsevents '.' WHERE `eventid`='.$this->eventid;

		return ($wpdb->query($sql) === false ? false : true);
	}
}
?>