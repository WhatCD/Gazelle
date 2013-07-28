<?

if (!check_perms('users_mod') ) {
	error(403);
}
if (is_number($_GET['id'])) {
	$ID = $_GET['id'];
	$Event = SiteHistory::get_event($ID);
}

if ($ID) {
	$Title = "Edit";
} else {
	$Title = "Create";
}
View::show_header($Title, "jquery.validate,form_validate,site_history");

?>

<div class="header">
<?	if ($ID) { ?>
		<h2>Edit event</h2>
<?	} else { ?>
		<h2>Create new event</h2>
<?	} ?>
</div>

<?
SiteHistoryView::render_edit_form($Event);
View::show_footer();