<?
View::show_header('Link an article');
?>
<div class="thin">
	<div class="box pad">
		<form class="add_form" name="aliases" action="wiki.php" method="post">
			<input type="hidden" name="action" value="link" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<div>
				<p>Paste a wiki link into the box below to link this search string or article name to the appropriate article.</p>
				<strong>Link </strong> <input type="text" name="alias" size="20" value="<?=display_str($Alias->convert($_GET['alias']))?>" />
				to <strong>URL</strong> <input type="text" name="url" size="50" maxlength="150" />
				<input type="submit" value="Submit" />
			</div>
		</form>
	</div>
</div>
<? View::show_footer(); ?>