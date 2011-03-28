<?
if(!check_perms('admin_dnu')) { error(403); }

authorize();

if($_POST['submit'] == 'Delete'){ //Delete
	if(!is_number($_POST['id']) || $_POST['id'] == ''){ error(0); }
	$DB->query('DELETE FROM do_not_upload WHERE ID='.$_POST['id']);
} else { //Edit & Create, Shared Validation
	$Val->SetFields('name', '1','string','The name must be set, and has a max length of 40 characters', array('maxlength'=>40, 'minlength'=>1));
	$Val->SetFields('comment', '0','string','The description has a max length of 255 characters', array('maxlength'=>255));
	$Err=$Val->ValidateForm($_POST); // Validate the form
	if($Err){ error($Err); }

	$P=array();
	$P=db_array($_POST); // Sanitize the form

	if($_POST['submit'] == 'Edit'){ //Edit
		if(!is_number($_POST['id']) || $_POST['id'] == ''){ error(0); }
		$DB->query("UPDATE do_not_upload SET
			Name='$P[name]',
			Comment='$P[comment]',
			UserID='$LoggedUser[ID]',
			Time='".sqltime()."'
			WHERE ID='$P[id]'");
	} else { //Create
		$DB->query("INSERT INTO do_not_upload 
			(Name, Comment, UserID, Time) VALUES
			('$P[name]','$P[comment]','$LoggedUser[ID]','".sqltime()."')");
	}
}

// Go back
header('Location: tools.php?action=dnu')
?>
