var elemStyles=Array();
var errorElems=Array();

function validEmail(str) {
	if (str.match(/^[_a-z0-9-]+([.+][_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$/i)) {			  
		return true;
	} else { 
		return false;
	}
}

function validLink(str) {
	if (str.match(/^(https?):\/\/([a-z0-9\-\_]+\.)+([a-z]{1,5}[^\.])(\/[^<>]+)*$/i)) {
		return true;
	} else { return false; }
}

function isNumeric(str,usePeriod) {
	matchStr='/[^0-9';
	if (usePeriod) {
		matchStr+='\.';
	}
	matchStr=']/';

	if (str.match(matchStr)) { return false; }
	return true;
}

function validDate(theDate) {
	days=0;

	theDate=theDate.split("/");
	month=theDate[0];
	day=theDate[1];
	year=theDate[2];

	if (!isNumeric(month) || !isNumeric(day) || !isNumeric(year)) { return false; }

	if (month==1) { days=31; }
	else if (month==2) { if ((year % 4 == 0 && year % 100 != 0) || year % 400 == 0) { days=29; } else { days=28; }}
	else if (month==3) { days=31; }
	else if (month==4) { days=30; }
	else if (month==5) { days=31; }
	else if (month==6) { days=30; }
	else if (month==7) { days=31; }
	else if (month==8) { days=31; }
	else if (month==9) { days=30; }
	else if (month==10) { days=31; }
	else if (month==11) { days=30; }
	else if (month==12) { days=31; }

	if (day>days || day==undefined || days==undefined || month==undefined || year==undefined || year.length<4) { return false; } else { return true; }
}

function showError(fields,alertStr) {
	var tField=Array();

	if (typeof(fields)=='object') { tField[0]=fields; } else { tField=fields.split(','); }
	for(s=0; s<=tField.length-1; s++) {
		if ($('#'+tField[s])) {
			$('#'+tField[s]).className=$('#'+tField[s]).className+" elem_error";
			if (s==0) {
				$('#'+tField[s]).focus();
				try { $('#'+tField[s]).select(); } catch (error) { }
			}

			errorElems[errorElems.length]=tField[s];
			if ($('#'+tField[s]).type!="select-one") {
				$('#'+tField[s]).onkeypress=function() { clearElemError(); };
			} else {
				$('#'+tField[s]).onchange=function() { clearElemError(); };
			}
		}
	}

	if (alertStr!="") { alert(alertStr); }
	return false;
}

function clearErrors(theForm) {
	elementList=document.forms[theForm].elements;
	for(x=0; x<=elementList.length-1; x++) {
		if (elementList[x].type!="submit" && elementList[x].type!="button") {
			if (!elemStyles[elementList[x].id]) {
				elemStyles[elementList[x].id]=elementList[x].className;
			}

			try {
				elementList[x].className=elemStyles[elementList[x].id];
			} catch (error) { }
		}
	}
}

function clearElemError(evt) {
	for(x=0; x<=errorElems.length-1; x++) {
		elem=$('#'+errorElems[x]);
		if ($('#'+elem).type!="select-one") {
			$('#'+elem).onkeypress="";
		} else {
			$('#'+elem).onchange="";
		}
		elem.className=elemStyles[elem.id];
	}
	errorElems=Array();
}
