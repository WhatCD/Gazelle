<?
if (!check_perms('admin_dnu')) {
	error(403);
}

authorize();

if ($_POST['submit'] == 'Reorder') { // Reorder
	foreach ($_POST['item'] as $Position => $Item) {
		$Position = db_string($Position);
		$Item = db_string($Item);
		$DB->query('
			UPDATE `do_not_upload`
			SET `Sequence` = ' . $Position . '
			WHERE `id` = '. $Item);
	}

} elseif ($_POST['submit'] == 'Delete') { //Delete
	if (!is_number($_POST['id']) || $_POST['id'] == '') {
		error(0);
	}
	$DB->query('
		DELETE FROM do_not_upload
		WHERE ID = '.$_POST['id']);
} else { //Edit & Create, Shared Validation
	$Val->SetFields('name', '1', 'string', 'The name must be set, have a maximum length of 100 characters, and have a minimum length of 5 characters.', array('maxlength' => 100, 'minlength' => 5));
	$Val->SetFields('comment', '0', 'string', 'The description has a maximum length of 255 characters.', array('maxlength' => 255));
	$Err = $Val->ValidateForm($_POST); // Validate the form
	if ($Err) {
		error($Err);
	}

	$P = array();
	$P = db_array($_POST); // Sanitize the form

	if ($_POST['submit'] == 'Edit') { //Edit
		if (!is_number($_POST['id']) || $_POST['id'] == '') {
			error(0);
		}
		$DB->query("
			UPDATE do_not_upload
			SET
				Name = '$P[name]',
				Comment = '$P[comment]',
				UserID = '$LoggedUser[ID]',
				Time = '".sqltime()."'
			WHERE ID = '$P[id]'");
	} else { //Create
		$DB->query("
			INSERT INTO do_not_upload
				(Name, Comment, UserID, Time, Sequence)
			VALUES
				('$P[name]','$P[comment]','$LoggedUser[ID]','".sqltime()."', 9999)");
	}
}

// Go back
header('Location: tools.php?action=dnu')
?>
