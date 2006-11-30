function printBackendOptions(aObjects,oOpt) {
	var myForm = oOpt.form;
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
	for(i=0;i < aObjects.length;i++) {
		var row = tbl.insertRow(lastRow);
		row.setAttribute('id', 'row_'+lastRow);
		
		var label = row.insertCell(0);
		label.setAttribute('class', 'tdlabel');
		if(aObjects[i].must == 1) {
			label.setAttribute('style', 'color:red;');
		}
		label.innerHTML = aObjects[i].key;
		
		var input = row.insertCell(1);
		input.setAttribute('class', 'tdfield');
		
		var sValue = "";
		if(aObjects[i].value != null) {
			sValue = aObjects[i].value;
		}
		
		input.innerHTML = "<input name='"+aObjects[i].key+"' id='"+aObjects[i].key+"' value='"+sValue+"' />";
		
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