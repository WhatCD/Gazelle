<?
if (!check_perms('users_view_email')) {
	error(403);
}

authorize();

if ($_POST['submit'] === 'Delete') { // Delete
	if (!is_number($_POST['id']) || $_POST['id'] === '') {
		error(0);
	}
	$DB->query("
		DELETE FROM email_blacklist
		WHERE ID = $_POST[id]");
} else { // Edit & Create, Shared Validation
	$Val->SetFields('email', '1', 'string', 'The email must be set', array('minlength'=>1));
	$Val->SetFields('comment', '0', 'string', 'The description has a max length of 255 characters', array('maxlength'=>255));
	$Err = $Val->ValidateForm($_POST);
	if ($Err) {
		error($Err);
	}

	$P = array();
	$P = db_array($_POST); // Sanitize the form

	if ($_POST['submit'] === 'Edit') { // Edit
		if (!is_number($_POST['id']) || $_POST['id'] === '') {
			error(0);
		}
		$DB->query("
			UPDATE email_blacklist
			SET
				Email = '$P[email]',
				Comment = '$P[comment]',
				UserID = '$LoggedUser[ID]',
				Time = '".sqltime()."'
			WHERE ID = '$P[id]'");
	} else { // Create
		$DB->query("
			INSERT INTO email_blacklist (Email, Comment, UserID, Time)
			VALUES ('$P[email]', '$P[comment]', '$LoggedUser[ID]', '".sqltime()."')");
	}
}

// Go back
header('Location: tools.php?action=email_blacklist')
?>
