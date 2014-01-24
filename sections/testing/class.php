<?
if (!check_perms('users_mod')) {
	error(404);
}

$Class = $_GET['name'];
if (!empty($Class) && !Testing::has_class($Class)) {
	error("Missing class");
}

View::show_header("Tests", "testing");

?>
<div class="header">
	<h2><?=$Class?></h2>
	<?=TestingView::render_linkbox("class"); ?>
</div>

<div class="thin">
	<?=TestingView::render_functions(Testing::get_testable_methods($Class));?>
</div>

<?
View::show_footer();


