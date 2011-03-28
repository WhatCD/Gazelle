function Clear(torrentid) {
	ajax.get("?action=notify_clearitem&torrentid=" + torrentid + "&auth=" + authkey, function() {
		$("#torrent" + torrentid).remove();
	});
}