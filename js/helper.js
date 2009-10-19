function fse_toogleAllday(obj) {
	var f = document.forms["event"];
	
	f.time_from.disabled = obj.checked;
	f.time_to.disabled = obj.checked;
	
	if (obj.checked == true) {
		f.time_from.value = '00:00';
		f.time_to.value = '00:00';
	}
}

function fse_toogleInputByCheckbox(objref, node, inverse) {
	var disabled = objref.checked;
	
	if (inverse == true) {
		disabled = !disabled;
	}
	
	document.getElementById(node).disabled = disabled;
}
