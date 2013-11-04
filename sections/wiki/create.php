<?
View::show_header('Create an article');
?>
<div class="thin">
	<div class="box pad">
		<form class="create_form" name="wiki_article" action="wiki.php" method="post">
			<input type="hidden" name="action" value="create" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<div>
				<h3>Title</h3>
				<input type="text" name="title" size="92" maxlength="100" />
<? /* if ($_GET['alias']) { ?>
				<input type="hidden" name="alias" value="<?=display_str(alias($_GET['alias']))?>" />
<? } else { ?>
				<h3>Alias</h3>
				<p>An exact search string or name that should lead to this article. (More can be added later)</p>
				<input type="text" name="alias" size="50" maxlength="50" />
<? } */?>
				<h3>Body </h3>
<?
	$ReplyText = new TEXTAREA_PREVIEW('body', 'body', '', 91, 22, true, false);

	if (check_perms('admin_manage_wiki')) { ?>
				<h3>Access</h3>
				<p>There are some situations in which the viewing or editing of an article should be restricted to a certain class.</p>
				<strong>Restrict read:</strong> <select name="minclassread"><?=class_list()?></select>
				<strong>Restrict edit:</strong> <select name="minclassedit"><?=class_list()?></select>
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
