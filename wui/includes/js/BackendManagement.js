function getBackendOptions(myType,myForm,myId) {
	var type = myType;
	
	if(!myId) {
		myId = "";
	}
	
	var form = document.getElementById(myForm);
	var tbl = document.getElementById('table_'+myForm);
	
	var toDelete = Array();
	
	// Remove old backend options
	for(i=0;i<tbl.tBodies[0].rows.length;i++) {
		var str = tbl.tBodies[0].rows[i].toSting;
		
		if(tbl.tBodies[0].rows[i].attributes.length > 0) {
			for(a=0;a<tbl.tBodies[0].rows[i].attributes.length;a++) {
				var key = tbl.tBodies[0].rows[i].attributes[0].nodeName;
				if(tbl.tBodies[0].rows[i].attributes[0].nodeName == 'id') {
					if(tbl.tBodies[0].rows[i].attributes[0].nodeValue.search("row_") != -1) {
						toDelete[toDelete.length] = i;
					}
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
	row.setAttribute('id', 'row_'+lastRow);
	var label = row.insertCell(0);
	label.innerHTML = "&nbsp;";
	var input = row.insertCell(1);
	input.innerHTML = "&nbsp;";
	
	lastRow++;
	
	for(key in backendOptions[type]) {
		var row = tbl.insertRow(lastRow);
		row.setAttribute('id', 'row_'+lastRow);
		
		var label = row.insertCell(0);
		label.setAttribute('class', 'tdlabel');
		if(backendOptions[type][key]['must'] == 1) {
			label.setAttribute('style', 'color:red;');
		}
		label.innerHTML = key;
		
		var input = row.insertCell(1);
		input.setAttribute('class', 'tdfield');
		
		var sValue = "";
		if(myId != "" && definedBackends[myId][key] != null) {
			sValue = definedBackends[myId][key];
		}
		
		input.innerHTML = "<input name='"+key+"' id='"+key+"' value='"+sValue+"' />";
		
		lastRow++;
	}
}

function check_backend_add() {
	return true;
}

function check_backend_edit() {
	return true;
}

function check_backend_del() {
	return true;
}