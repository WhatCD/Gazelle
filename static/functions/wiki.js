function Remove_Alias(alias) {
	ajax.get("wiki.php?action=delete_alias&auth=" + authkey + "&alias=" + alias, function(response) {
		$('#alias_' + alias).ghide();
	});
}
