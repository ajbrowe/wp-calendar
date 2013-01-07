function fse_togglePostEvent(checked) {
	if (checked)
		jQuery('#fseventdata').slideDown();
	else
		jQuery('#fseventdata').slideUp();
}
function fse_toogleAllday(checked) {
	
	jQuery('#time_from').attr('disabled', checked);
	jQuery('#time_to').attr('disabled', checked);
	
	if (checked) {
		jQuery('#time_from').val('00:00');
		jQuery('#time_to').val('23:59');
	}
}

function fse_toogleInputByCheckbox(objref, node, inverse) {
	var disabled = objref.checked;
	
	if (inverse == true) {
		disabled = !disabled;
	}
	
	document.getElementById(node).disabled = disabled;
}

function fse_validateDate(objref, format, sep) {
	if (objref.value == '')
		return;
	
	
	var date = fse_transformStringToDate(objref.value, format, sep);
	
	if (date == false) {
		return false;
	} else {
		objref.value = fse_transformDateToString(date, format, sep);
		return true;
	}
}

function fse_validateTime(objref) {
	if (objref.value == '')
		return;
	
	var time = fse_transformStringToTime(objref.value);
	
	if (time == false) {
		return false;
	} else {
		objref.value = fse_transformTimeToString(time);
		return true;
	}
}

function fse_updateOtherDate(objref, format, sep) {
	if (objref.name == "event_from") {
		var datefrom = fse_transformStringToDate(objref.value, format, sep);
		if (datefrom == false) { return; }
		var dateto   = fse_transformStringToDate(objref.form.event_to.value, format, sep);
	} else {
		var dateto   = fse_transformStringToDate(objref.value, format, sep);
		if (dateto == false) { return; }
		var datefrom = fse_transformStringToDate(objref.form.event_from.value, format, sep);
	}
	
	if (datefrom == false || dateto == false || dateto < datefrom) {
		if (objref.name == "event_from") {
			objref.form.event_to.value = objref.value;
		} else {
			objref.form.event_from.value = objref.value;
		}
	}
}

function fse_updateOtherTime(objref, format, sep) {
	
	// Only if on the same date!
	var datefrom = fse_transformStringToDate(objref.form.event_from.value, format, sep);
	var dateto   = fse_transformStringToDate(objref.form.event_to.value, format, sep);
	
	if (datefrom == false || 
	    dateto   == false || 
	    datefrom.getTime() != dateto.getTime()) {
		return;
	}
	
	if (objref.name == "event_tfrom") {
		var timefrom = fse_transformStringToTime(objref.value);
		if (timefrom == false) { return; }
		var timeto   = fse_transformStringToTime(objref.form.event_tto.value);
	} else {
		var timeto   = fse_transformStringToTime(objref.value);
		if (timeto == false) { return; }
		var timefrom = fse_transformStringToTime(objref.form.event_tfrom.value);
	}
	
	if (timeto == false || timefrom == false || timeto < timefrom) {
		if (objref.name == "event_tfrom") {
			objref.form.event_tto.value = objref.value;
		} else {
			objref.form.event_tfrom.value = objref.value;
		}
	}
}

function fse_transformStringToDate(input, format, sep) {
	var d = -1;
	var m = -1;
	var y = -1;
	
	var tok = "";
	
	if (input == '')
		return false;
	
	if (input.indexOf(sep) > -1) {
		var token = input.split(sep);
		for (var i=0; i<format.length; i++) {
			tok = format.substr(i, 1).toLowerCase();
			switch(tok) {
				case "d":
					d = parseInt(token[i], 10);
					break;
				case "m":
					m = parseInt(token[i], 10) - 1;
					break;
				case "y":
					y = parseInt(token[i], 10);
					break;
			}
		}
	} else {
		if (input.length == 8 || 
				input.length == 6) {
			var pos = 0;
			var len = 0;
			var val = "";
			for (var i=0; i<format.length; i++) {
				tok = format.substr(i, 1).toLowerCase();
				if (tok == 'y' && input.length == 8)
					len = 4;
				else
					len = 2;
				
				val = input.substr(pos, len);
				pos += len;
				switch(tok) {
					case "d":
						d = parseInt(val, 10);
						break;
					case "m":
						m = parseInt(val, 10) - 1;
						break;
					case "y":
						y = parseInt(val, 10);
						break;
				}
			}
		} else {
			return false;
		}
	}
	
	if (y > -1 && y < 100) {
		if (y >= 70)
			y += 1900;
		else
			y += 2000;
	}	
	
	if (d<1 || d>31)
		return false;
	if (m<0 || m>11)
		return false;
	if (y < 1900 || y > 2099)
		return false;
	
	// Create a new date
	var date = new Date(y, m, d);
	
	return date;
}

function fse_transformDateToString(date, format, sep) {
	var d = date.getDate();
	var m = date.getMonth() + 1;
	var y = date.getFullYear();
	
	// Write back
	var out = "";
	for (var i=0; i<format.length; i++) {
		tok = format.substr(i, 1).toLowerCase();
		if (i>0)
			out += sep;
		
		switch(tok) {
			case "d":
				out += (d < 10 ? "0" : "") + d;
				break;
			case "m":
				out += (m < 10 ? "0" : "") + m;
				break;
			case "y":
				out += y;
				break;
		}
	}
	
	return out;
}

function fse_transformStringToTime(input) {
	var h = -1;
	var m = -1;
	if (input.indexOf(":") > -1) {
		var token = input.split(":");
		h = token[0];
		m = token[1];
	} else if (input.length <= 2) {
		h = input;
		m = 0;
	} else if (input.length == 3) {
		h = input.substr(0, 1);
		m = input.substr(1, 2);
	} else if (input.length == 4) {
		h = input.substr(0, 2);
		m = input.substr(2, 2);
	}
	
	if (m < 0 || m > 59)
		return false;
	
	if (h < 0 || h > 23)
		return false;
	
	var time = new Date();
	time.setHours(h, m, 0, 0);
	
	return time;
}

function fse_transformTimeToString(time) {
	return (time.getHours() < 10 ? "0" : "") + time.getHours() + ":" +
	       (time.getMinutes() < 10 ? "0" : "") + time.getMinutes();
}

function fse_overviewFilter(field, value) {
	document.forms['event'].elements[field].value = value;
	document.forms['event'].submit();
}

function fse_overviewSort(field) {
	var dir = 'ASC';
	if (document.forms['event'].elements['event_sort'].value == field) {
		if (document.forms['event'].elements['event_sortdir'].value == 'ASC') {
			dir = 'DESC';
		}
	}
	
	document.forms['event'].elements['event_sort'].value = field;
	document.forms['event'].elements['event_sortdir'].value = dir;
	document.forms['event'].submit();
}

function fse_disableAutoSynchronization() {
	
}