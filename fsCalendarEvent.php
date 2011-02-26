<?php
class fsEvent {
	var $eventid = 0;
	var $postid = 0;
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
			$sql = $wpdb->prepare('SELECT eventid FROM '.$wpdb->prefix.'fsevents '.' WHERE postid='.$this->postid);
			$this->eventid = $wpdb->get_var($sql);
			if (empty($this->eventid))
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
		
		$this->postid = $ret->postid;
		$this->updatedbypost = ($ret->updatedbypost == true ? true : false);
		$this->subject = $ret->subject;
		$this->location = $ret->location;
		$this->description = $ret->description;
		$this->tsfrom = $ret->tsfrom;
		$this->tsto = $ret->tsto;
		$this->allday = ($ret->allday == true ? true : false);
		$this->author = $ret->author;
		$this->publishauthor = $ret->publishauthor;
		$this->createdate = $ret->createdate;
		$this->publishdate = $ret->publishdate;
		$this->state = $ret->state;
		
		$this->categories = $wpdb->get_col('SELECT catid FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$this->eventid);
		
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
			$this->date_admin_from = fsCalendar::date_i18n($this->date_admin_format, $this->tsfrom);
			$this->date_admin_to   = fsCalendar::date_i18n($this->date_admin_format, $this->tsto);
			$this->time_admin_from = fsCalendar::date_i18n($this->time_admin_format, $this->tsfrom);
			$this->time_admin_to   = fsCalendar::date_i18n($this->time_admin_format, $this->tsto);
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
		
		return fsCalendar::date_i18n($fmt, $this->tsfrom);
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
		
		return fsCalendar::date_i18n($fmt, $this->tsto);
	}
	
	function getDescription() {
		return apply_filters('the_content', $this->description);
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
		
		$ts_from = mktime($ft['h'], $ft['m'], 0, $fd['m'], $fd['d'], $fd['y']);
		$ts_to   = mktime($tt['h'], $tt['m'], 0, $td['m'], $td['d'], $td['y']);
		
		if (empty($this->state)) {
			$this->state = 'draft';
		}
		
		if ($ts_from > $ts_to) {
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
					SET subject=%s, tsfrom=$ts_from, tsto=$ts_to, allday=%d, description=%s, location=%s, state=%s, 
					updatedbypost=%d 
					WHERE eventid=$this->eventid",
		        	$this->subject, ($this->allday == true ? 1 : 0),$this->description, $this->location, $this->state, ($this->updatedbypost == true ? 1 : 0));
			} else {
				$errors[] = __('No permission to edit event', fsCalendar::$plugin_textdom);
			}
		} else {
			if ($fsCalendar->userCanAddEvents()) {
				$time = time();
				
				if (empty($this->postid))
					$postid = 'NULL';
				else
					$postid = intval($this->postid);
				
				$sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix.'fsevents '."
					(subject, tsfrom, tsto, allday, description, location, author, createdate, state, postid, updatedbypost)
					VALUES (%s, $ts_from, $ts_to, %d, %s, %s, $user_ID, $time, %s, $postid, %d)", 
		        	$this->subject, ($this->allday == true ? 1 : 0), $this->description, $this->location, $this->state, ($this->updatedbypost == true ? 1 : 0));
			} else {
				$errors[] = __('No permission to create event', fsCalendar::$plugin_textdom);
			}
		}
        
		// Error -> return them
		if (count($errors) > 0) {
			return $errors;
		}
		
        if ($wpdb->query($sql) !== false) {
        	if ($this->eventid <= 0) {
	        	$this->eventid = $wpdb->insert_id;
	        	
	        	$this->author = $user_ID;
	        	$this->createdate = $time;
	        	
	        	$u = new WP_User($user_ID);
	        	$this->author_t = $u->display_name;
	        	unset($u);
	        	
	        	$action = 'edit'; // Switch to edit mode!
        	} else {
        		$success[] = __('Event updated', fsCalendar::$plugin_textdom);
        	}
        	
        	// Handle categories
        	$ret_cats = $wpdb->get_col('SELECT catid FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$this->eventid);
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
        			$sql = 'DELETE FROM '.$wpdb->prefix.'fsevents_cats WHERE eventid='.$this->eventid.' AND catid='.$c;
        			$wpdb->query($sql);
        		}
        	}
        	return true;
        	
        } else {
        	$errors[] = $sql;
        	$errors[] = __('DB Error', fsCalendar::$plugin_textdom);
        	return $errors;
        }
	}
	
	/**
	 * Publishes the current event
	 * @use To check the result compare it with true using === (3!)
	 * @return True, if successfull or an array of error messages
	 */
	function setStatePublished() {
		global $user_ID;
		global $wpdb;
		
		if (empty($this->eventid)) {
			return __('Event does not exist');
		}
		if ($this->eventid > 0 && !$fsCalendar->userCanEditEvent()) {
			return __('No permission to edit event', fsCalendar::$plugin_textdom);
		}
		
		$time = time();
		if ($wpdb->query('UPDATE '.$wpdb->prefix.'fsevents '.' 
						  SET state="publish", publishauthor="'.intval($user_ID).'", publishdate='.$time.' 
						  WHERE eventid='.$this->eventid) !== false) {
			$this->state = 'publish';
			$this->publishauthor = $user_ID;
			$this->publishdate   = $time;
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
	 * @return True, if successfull or an array of error messages
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
		
		if ($wpdb->query('UPDATE '.$wpdb->prefix.'fsevents '.' 
					  SET state="draft", publishdate=NULL, publishauthor=NULL 
					  WHERE eventid='.$this->eventid) !== false) {
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
				SET updatedbypost=0 
				WHERE eventid=$this->eventid");
			$wpdb->query($sql);
			
			$this->updatedbypost = false;
		}
	}
}
?>