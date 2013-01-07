<?php
/**
 * Returns a single events as an object
 * @param $eventid Event Id
 * @return Object of fsEvent
 */
function fse_get_event($eventid) {
	$e = new fsEvent($eventid, '', false);
	if ($e->eventid == 0)
		return false;
	else
		return $e;
}

function fse_print_events($args) {
	global $fsCalendar;
	
	return $fsCalendar->printEvents($args);
}

function fse_print_events_list($args) {
	global $fsCalendar;
	
	return $fsCalendar->printEventsList($args);
}

function fse_get_events($args = array()) {
	global $fsCalendar;
	
	return $fsCalendar->getEventsExternal($args);
}

function fse_get_ical_feed_url($categories = '') {
	$url = get_bloginfo('url');
	if ($url[strlen($url)-1] != '/') { $url .= '/'; }
	$url .= 'feed/ical';
	if (!empty($categories)) {
		$url .= '?categories='.urlencode($categories);
	}
	return $url;
}

function fse_print_events_feed($args = array()) {
	global $fsCalendar;
	
	$feed = $fsCalendar->getEventsFeed($args);
	header("Content-Type: text/Calendar");
    header("Content-Disposition: inline; filename=icalfeed.ics");
    //echo '<pre>';
	echo $feed;
	//echo '</pre>';
}

function the_event_id() {
	echo get_the_event_id();
}

function get_the_event_id() {
	if (isset($_GET['event'])) {
		return intval($_GET['event']);
	} elseif (isset($_POST['event'])) {
		return intval($_GET['event']);
	}	
}
function the_comment_event_meta() {
	echo '<input type="hidden" name="fse_eventid" value="'.get_the_event_id().'" />';
}
?>