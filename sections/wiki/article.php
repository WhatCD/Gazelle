<?
include(SERVER_ROOT.'/classes/class_text.php'); // Text formatting class
$Text = new TEXT;

if(!empty($_GET['id']) && is_number($_GET['id'])){ //Visiting article via ID
	$ArticleID = $_GET['id'];
} elseif ($_GET['name'] != '') { //Retrieve article ID via alias.
	$ArticleID = $Alias->to_id($_GET['name']);
} else { //No ID, No Name
	//error(404);
	error('Unknown article ['.display_str($_GET['id']).']');
}

if(!$ArticleID) { //No article found
	show_header('No article found');
?>
<div class="thin">
	<h2>No article found</h2>
	<div class="box pad" style="padding:10px 10px 10px 20px;">
		There is no article matching the name you requested.
		<ul>
			<li><a href="wiki.php?action=search&amp;search=<?=display_str($_GET['name'])?>">Search</a> for an article similar to this.</li>
			<li><a href="wiki.php?action=link&amp;alias=<?=display_str($Alias->convert($_GET['name']))?>">Link</a> this to an existing article.</li>
			<li><a href="wiki.php?action=create&amp;alias=<?=display_str($Alias->convert($_GET['name']))?>">Create</a> an article in its place.</li>
		</ul>
	</div>
</div>
<?
	show_footer();
	die();
}
$Article = $Alias->article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName, $Aliases, $UserIDs) = array_shift($Article);
if($Read > $LoggedUser['Class']){ error(404); }

show_header($Title,'wiki,bbcode');
?>
<div class="thin">
	<h2><?=$Title?></h2>
	<div class="linkbox box">
			<a href="wiki.php?action=create">[Create]</a>
			<a href="wiki.php?action=edit&amp;id=<?=$ArticleID?>">[Contribute]</a>
			<a href="wiki.php?action=revisions&amp;id=<?=$ArticleID?>">[History]</a>
<? if(check_perms('admin_manage_wiki') && $_GET['id'] != '136'){ ?>
			<a href="wiki.php?action=delete&amp;id=<?=$ArticleID?>&amp;authkey=<?=$LoggedUser['AuthKey']?>" onclick="return confirm('Are you sure you want to delete?\nYes, DELETE, not as in \'Oh hey, if this is wrong we can get someone to magically undelete it for us later\' it will be GONE.\nGiven this new information, do you still want to DELETE this article and all its revisions and all its alias\' and act like it never existed?')">[Delete]</a>
<? } ?>
			<!--<a href="reports.php?action=submit&amp;type=wiki&amp;article=<?=$ArticleID ?>">[Report]</a>-->
	</div>
	<br />
	<div class="sidebar">
		<!--
		<div class="box pad">
			Table of Contents
			<ul>
				<li>Deferred for later with the KB broken</li>
			</ul>
		</div>
		-->
		<div class="box pad center">
			<form action="wiki.php" method="get">
				<input type="hidden" name="action" value="search" />
				<input 
					onfocus="if (this.value == 'Search Articles') this.value='';"
					onblur="if (this.value == '') this.value='Search Articles';"
					value="Search Articles" type="text" name="search" size="20"
				/>
				<input value="Search" type="submit" class="hidden" />
			</form>
			<br style="line-height:10px;"/>
			<strong><a href="wiki.php?action=browse">Browse articles</a></strong>
		</div>
		<div class="box pad">
			<ul>
				<li>
					<strong>Protection:</strong>
					<ul>
						<li>Read: <?=$ClassLevels[$Read]['Name']?></li>
						<li>Edit: <?=$ClassLevels[$Edit]['Name']?></li>
					</ul>
				 </li>
				<li>
					<strong>Details:</strong>
					<ul>
						<li>Version: r<?=$Revision?></li>
						<li>Last edited by: <?=format_username($AuthorID, $AuthorName)?></li>
						<li>Last updated: <?=time_diff($Date)?></li>
					</ul>
				</li>
				<li>
					<strong>Aliases:</strong>
					<ul>
<?	if($Aliases!=$Title){
		$AliasArray = explode(',', $Aliases);
		$UserArray = explode(',', $UserIDs);
		$i = 0;
		foreach($AliasArray as $AliasItem){
?>
						<li id="alias_<?=$AliasItem?>"><a href="wiki.php?action=article&amp;name=<?=$AliasItem?>"><?=cut_string($AliasItem,20,1)?></a><? if(check_perms('admin_manage_wiki')){ ?> <a href="#" onclick="Remove_Alias('<?=$AliasItem?>');return false;" title="Delete Alias">[X]</a> <a href="user.php?id=<?=$UserArray[$i]?>" title="View User">[U]</a><? } ?></li>
<?			$i++;
		} 
	}
?>
					</ul>
				</li>
			</ul>
		</div>
<? if($Edit <= $LoggedUser['Class']){ ?>
		<div class="box">
			<div style="padding:5px;">
				<form action="wiki.php" method="post">
					<input type="hidden" name="action" value="add_alias" />
					<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
					<input type="hidden" name="article" value="<?=$ArticleID?>" />
					<input
						onfocus="if (this.value == 'Add Alias') this.value='';"
						onblur="if (this.value == '') this.value='Add Alias';"
						value="Add Alias" type="text" name="alias" size="20"
					/>
					<input type="submit" value="+" />
				</form>
			</div>
		</div>
<? } ?>
	</div>
	<div class="main_column">
	<div class="box">
		<div class="pad"><?=$Text->full_format($Body)?></div>
	</div>
	</div>
</div>
<? show_footer(); ?>
