<?

$CollageID = $_GET['collageid'];
if (!is_number($CollageID) || !$CollageID) {
	error(404);
}

$DB->query("
	SELECT Name, UserID
	FROM collages
	WHERE ID = '$CollageID'");
list($Name, $UserID) = $DB->next_record();

if (!check_perms('site_collages_delete') && $UserID != $LoggedUser['ID']) {
	error(403);
}

View::show_header('Delete collage');
?>
<div class="thin center">
	<div class="box" style="width: 600px; margin: 0px auto;">
		<div class="head colhead">
			Delete collage
		</div>
		<div class="pad">
			<form class="delete_form" name="collage" action="collages.php" method="post">
				<input type="hidden" name="action" value="take_delete" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="collageid" value="<?=$CollageID?>" />
				<div class="field_div">
					<strong>Reason: </strong>
					<input type="text" name="reason" size="40" />
				</div>
				<div class="submit_div">
					<input value="Delete" type="submit" />
				</div>
			</form>
		</div>
	</div>
</div>
<?
View::show_footer();
?>
