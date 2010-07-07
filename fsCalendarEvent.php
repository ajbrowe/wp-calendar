<?php
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
			$this->date_admin_from = date_i18n($this->date_admin_format, $this->tsfrom);
			$this->date_admin_to   = date_i18n($this->date_admin_format, $this->tsto);
			$this->time_admin_from = date_i18n($this->time_admin_format, $this->tsfrom);
			$this->time_admin_to   = date_i18n($this->time_admin_format, $this->tsto);
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
		
		return date_i18n($fmt, $this->tsfrom);
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
		
		return date_i18n($fmt, $this->tsto);
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
	
}
?>