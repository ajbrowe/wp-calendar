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
?>