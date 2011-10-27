function Add(input) {
	if(input.checked == false) {
		Cancel();
	} else {
		if(document.getElementById("choices").raw().value == "") {
			document.getElementById("choices").raw().value += input.name;
		} else {
			document.getElementById("choices").raw().value += "|" + input.name;
		}
	}
}

function Cancel() {
	var e=document.getElementsByTagName("input");
	for(i=0;i<e.length;i++){
		if(e[i].type=="checkbox"){
			e[i].checked=false;
		}
	}
	document.getElementById("choices").raw().value = "";
}

function CollageSubscribe(collageid) {
	ajax.get("userhistory.php?action=collage_subscribe&collageid=" + collageid + "&auth=" + authkey, function() {
		var subscribeLink = $("#subscribelink" + collageid).raw();
		if(subscribeLink) {
			if(subscribeLink.firstChild.nodeValue.substr(1,1) == 'U') {
				subscribeLink.firstChild.nodeValue = "[Subscribe]";
			} else {
				subscribeLink.firstChild.nodeValue = "[Unsubscribe]";
			}
		}
	});
}