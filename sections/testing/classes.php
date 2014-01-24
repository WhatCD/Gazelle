<?
if (!check_perms('users_mod')) {
	error(404);
}

View::show_header("Tests");

?>
<div class="header">
	<h2>Tests</h2>
	<? TestingView::render_linkbox("classes"); ?>
</div>

<div class="thin">
	<? TestingView::render_classes(Testing::get_classes());?>
</div>

<?
View::show_footer();


