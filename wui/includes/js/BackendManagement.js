function printBackendOptions(aObjects,oOpt) {
	var myForm = oOpt.form;
	var form = document.getElementById(myForm);
	var tbl = document.getElementById('table_'+myForm);
	
	var toDelete = Array();
	
	// Remove old backend options
	for(i=0;i<tbl.tBodies[0].rows.length;i++) {
		if(tbl.tBodies[0].rows[i].attributes.length > 0) {
			if(tbl.tBodies[0].rows[i].id != '') {
				if(tbl.tBodies[0].rows[i].id.search("row_") != -1) {
					toDelete[toDelete.length] = i;
				}
			}
		}
	}
	
	toDelete.reverse();
	for(i=0;i<toDelete.length;i++) {
		tbl.deleteRow(toDelete[i]);
	}
	
	var lastRow = tbl.rows.length-1;
	
	// Add spacer row
	var row = tbl.insertRow(lastRow);
	//row.setAttribute('id', 'row_'+lastRow);
	row.id = 'row_'+lastRow;
	var label = row.insertCell(0);
	label.innerHTML = "&nbsp;";
	var input = row.insertCell(1);
	input.innerHTML = "&nbsp;";
	
	lastRow++;
	for(i=0;i < aObjects.length;i++) {
		var row = tbl.insertRow(lastRow);
		//row.setAttribute('id', 'row_'+lastRow);
		row.id = 'row_'+lastRow;
		
		var label = row.insertCell(0);
		//label.setAttribute('class', 'tdlabel');
		label.className = "tdlabel";
		if(aObjects[i].must == 1) {
			label.setAttribute('style', 'color:red;');
		}
		label.innerHTML = aObjects[i].key;
		
		var input = row.insertCell(1);
		//input.setAttribute('class', 'tdfield');
		input.className = "tdfield";
		
		var sValue = "";
		if(aObjects[i].value != null) {
			sValue = aObjects[i].value;
		}
		
		input.innerHTML = "<input name='"+aObjects[i].key+"' id='"+aObjects[i].key+"' value='"+sValue+"' />";
		
		lastRow++;
	}
}

function check_backend_add() {
	form = document.backend_add;
	
	if(form.backend_id.value == '') {
		alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~backend_id'));
		return false;
	}
	if(form.backendtype.value == '') {
		alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~backendtype'));
		return false;
	}
	
	for(i=0;i<form.elements.length;i++) {
		// backend_id und backendtype are handled before this loop
		if(form.elements[i].name != 'backend_id' && form.elements[i].name != 'backendtype') {
			// if this value is a "must" and emtpy, error
			if(backendOptions[form.backendtype.value][form.elements[i].name]['must'] == '1' && form.elements[i].value == '') {
				alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+form.elements[i].name));
				return false;
			}
		}
	}
	
	return true;
}

function check_backend_edit() {
	form = document.backend_edit;
	
	if(form.backend_id.value == '') {
		alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~backend_id'));
		return false;
	}
	
	for(i=0;i<form.elements.length;i++) {
		// backend_id und backendtype are handled before this loop
		if(form.elements[i].name != 'backend_id' && form.elements[i].name != 'backendtype') {
			// if this value is a "must" and emtpy, error
			if(backendOptions[definedBackends[form.backend_id.value]['backendtype']][form.elements[i].name]['must'] == '1' && form.elements[i].value == '') {
				alert(printLang(lang['mustValueNotSet'],'ATTRIBUTE~'+form.elements[i].name));
				return false;
			}
		}
	}
	
	return true;
}

function check_backend_del() {
	form = document.backend_del;
	
	if(form.backend_id.value == '') {
		alert('backend_id not set. You have to set a backend_id.');
		
		return false;	
	}
	
	//FIXME: Check if backend is used in any maps/objects
	
	return true;
}