<?
if(!isset($_GET['id']) || !is_number($_GET['id'])) { error(404); }
$ArticleID = $_GET['id'];

$Latest = $Alias->article($ArticleID);
list($Revision, $Title, $Body, $Read, $Edit, $Date, $AuthorID, $AuthorName) = array_shift($Latest);
if($Edit > $LoggedUser['Class']){ error(404); }

show_header($Title." Aliases");
?>
<h2><a href="wiki.php?action=article&id=<?=$ArticleID?>"><?=$Title?></a> Aliases</h2>
<div class="linkbox">
	Aliases are exact search strings or names that can be used to link to an article. [[Alias]]
</div>
<div class="thin">
	<form action="wiki.php" method="get">
		<input type="hidden" name="action" id="action" value="compare" />
		<input type="hidden" name="id" id="id" value="<?=$ArticleID?>" />
		<table>
			<tr class="colhead">
				<td>Add an alias to this article</td>
			</tr>
			<tr>
				<td>
					<input type="hidden" name="action" value="link" />
					<input type="text" name="alias" size="20" />
				<input type="submit" value="Submit" />
				</td>
			</tr>
		</table>
		<br />
		<table>
			<tr class="colhead">
				<td>Alias</td>
				<td>Remove</td>
			</tr>
			<tr>
				<td><?=$Revision?></td>
				<td><?=$Title?></td>

			</tr>
<? 	
$DB->query("SELECT Alias FROM wiki_aliases WHERE ArticleID='$ArticleID'");
while(list($Revision, $Title, $AuthorID, $AuthorName, $Date) = $DB->next_record()) { ?>
			<tr>
				<td><?=$Revision?></td>
				<td><?=$Title?></td>
			</tr>
<? } ?>
			<tr>
				<td class="center" colspan="6">
					<input type="submit" value="Compare" />
				</td>
			</tr>
		</table>
	</form>
</div>
<? show_footer(); ?>
