function Subscribe(topicid) {
	ajax.get("userhistory.php?action=thread_subscribe&topicid=" + topicid + "&auth=" + authkey, function() {
		if($("#subscribelink" + topicid).raw().firstChild.nodeValue.substr(1,1) == 'U') {
			$("#subscribelink" + topicid).raw().firstChild.nodeValue = "[Subscribe]";
		} else {
			$("#subscribelink" + topicid).raw().firstChild.nodeValue = "[Unsubscribe]";
		}
	});
}

function Collapse() {
	var hide = ($('#collapselink').raw().innerHTML.substr(0,1) == 'H' ? 1 : 0);
	if($('.row').results() > 0) {
		$('.row').toggle();
	}
	if(hide) {
		$('#collapselink').raw().innerHTML = 'Show post bodies';
	} else {
		$('#collapselink').raw().innerHTML = 'Hide post bodies';
	}
}
