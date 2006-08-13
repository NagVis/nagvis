// function that checks the object is valid : all the properties marked with a * (required) have a value
// if the object is valid it writes the list of its properties/values in an invisible field, which will be passed when the form is submitted
function check_object() {
	object_name='';
	line_type='';
	iconset='';
	x='';
	y='';
	
	for(i=0;i<document.addmodify.elements.length;i++) {
		if(document.addmodify.elements[i].type != 'submit' && document.addmodify.elements[i].type != 'hidden') {
		
			if(document.addmodify.elements[i].name.substring(document.addmodify.elements[i].name.length-6,document.addmodify.elements[i].name.length)=='_name') {
				object_name=document.addmodify.elements[i].value;
			}
			if(document.addmodify.elements[i].name == 'iconset') {
				iconset=document.addmodify.elements[i].value;
			}
			if(document.addmodify.elements[i].name == 'x') {
				x=document.addmodify.elements[i].value;
			}			
			if(document.addmodify.elements[i].name == 'y') {
				y=document.addmodify.elements[i].value;
			}
			
			if(document.addmodify.elements[i].name == 'allowed_for_config') {
				users_tab=document.addmodify.elements[i].value.split(',');
				suicide=true;
				for(k=0;k<users_tab.length;k++) {
					if ( (users_tab[k]=='EVERYONE') || (users_tab[k]==user) ) { suicide=false; }
				}
				if(suicide) {
					mess=lang[50];
					alert(mess);
					document.addmodify.properties.value='';
					document.addmodify.elements[i].focus();
					return false;
				}
			}		

			if(document.addmodify.elements[i].value != '') {
				if(document.addmodify.elements[i].name.charAt(document.addmodify.elements[i].name.length-1) == '*') {
					document.addmodify.properties.value=document.addmodify.properties.value+'^'+document.addmodify.elements[i].name.substring(0,document.addmodify.elements[i].name.length-1)+'='+document.addmodify.elements[i].value;
				} else {
					if(document.addmodify.elements[i].name=='line_type') {
						line_type=object_name.split(",").length+document.addmodify.elements[i].value;
						document.addmodify.properties.value=document.addmodify.properties.value+'^'+document.addmodify.elements[i].name+'='+line_type;
					} else {
						document.addmodify.properties.value=document.addmodify.properties.value+'^'+document.addmodify.elements[i].name+'='+document.addmodify.elements[i].value;
					}
				}
			} else {
				if(document.addmodify.elements[i].name.charAt(document.addmodify.elements[i].name.length-1) == '*') {
					mess=lang[9];
					alert(mess);
					document.addmodify.properties.value='';
					document.addmodify.elements[i].focus();
					return false;
				}
			}
		}
	}
	document.addmodify.properties.value=document.addmodify.properties.value.substring(1,document.addmodify.properties.value.length);
	
	// we make some post tests (concerning the line_type and iconset values)
	if(line_type != '') {
		// we verify that the current line_type is valid
		valid_list=new Array("10","11","20");
		for(j=0;valid_list[j]!=line_type && j<valid_list.length;j++);
		if(j==valid_list.length) {
			mess=lang[10];
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
		// we verify we don't have both iconset and line_type defined
		if(iconset != '') {
			mess=lang[121];
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
		// we verify we have 2 x coordinates and 2 y coordinates
		if(x.split(",").length != 2) {
			mess=lang[121];
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
		
		if(y.split(",").length != 2) {
			mess=lang[122];
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		}
	}
	
	if(x.split(",").length > 1) {
		if(x.split(",").length != 2) {
			mess=lang[131];
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		} else {
			if(line_type == '') {
				mess=lang[141];
				alert(mess);
				document.addmodify.properties.value='';
				return false;
			}
		}
	}
	
	if(y.split(",").length > 1) {
		if(y.split(",").length != 2) {
			mess=lang[132];
			alert(mess);
			document.addmodify.properties.value='';
			return false;
		} else {
			if(line_type == '') {
				mess=lang[142];
				alert(mess);
				document.addmodify.properties.value='';
				return false;
			}
		}
	}
	return true;
}