<?
if (!isset($_GET['id']) || !is_number($_GET['id'])) {
	error(404);
}
$ArticleID = (int)$_GET['id'];

$Article = Wiki::get_article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $Author) = array_shift($Article);
if ($Edit > $LoggedUser['EffectiveClass']) {
	error('You do not have access to edit this article.');
}

View::show_header('Edit '.$Title);
?>
<div class="thin">
	<div class="box pad">
		<form class="edit_form" name="wiki_article" action="wiki.php" method="post">
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="id" value="<?=$ArticleID?>" />
			<input type="hidden" name="revision" value="<?=$Revision?>" />
			<div>
				<h3>Title</h3>
				<input type="text" name="title" size="92" maxlength="100" value="<?=$Title?>" />
				<h3>Body </h3>
<?
	$ReplyText = new TEXTAREA_PREVIEW('body', 'body', $Body, 91, 22, true, false);

	if (check_perms('admin_manage_wiki')) {
?>
				<h3>Access</h3>
				<p>There are some situations in which the viewing or editing of an article should be restricted to a certain class.</p>
				<strong>Restrict read:</strong> <select name="minclassread"><?=class_list($Read)?></select>
				<strong>Restrict edit:</strong> <select name="minclassedit"><?=class_list($Edit)?></select>
<?	} ?>
				<div style="text-align: center;">
					<input type="button" value="Preview" class="hidden button_preview_<?=$ReplyText->getID()?>" tabindex="1" />
					<input type="submit" value="Submit" />
				</div>
			</div>
		</form>
	</div>
</div>
<? View::show_footer(); ?>
