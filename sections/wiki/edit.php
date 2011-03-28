<?
if(!is_number($_GET['id']) || $_GET['id'] == ''){ error(404); }
$ArticleID=$_GET['id'];

$Article = $Alias->article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $Author) = array_shift($Article);
if($Edit > $LoggedUser['Class']){ 
	error('You do not have access to edit this article.');
}

show_header('Edit '.$Title);
?>
<div class="thin">
	<div class="box pad">
		<form action="wiki.php" method="post">
			<input type="hidden" name="action" value="edit" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="id" value="<?=$ArticleID?>" />
			<input type="hidden" name="revision" value="<?=$Revision?>" />
			<div>
				<h3>Title</h3>
				<input type="text" name="title" size="92" maxlength="100" value="<?=$Title?>" />
				<h3>Body </h3>
				<textarea name="body" cols="91" rows="22" style="width:95%"><?=$Body?></textarea>
<? if(check_perms('admin_manage_wiki')){ ?>
				<h3>Access</h3>
				<p>There are some situations in which the viewing or editing of an article should be restricted to a certain class.</p>
				<strong>Restrict Read:</strong> <select name="minclassread"><?=class_list($Read)?></select>
				<strong>Restrict Edit:</strong> <select name="minclassedit"><?=class_list($Edit)?></select>
<? } ?>
				<div style="text-align: center;">
					<input type="submit" value="Submit" />
				</div>
			</div>
		</form>
	</div>
</div>
<? show_footer(); ?>
