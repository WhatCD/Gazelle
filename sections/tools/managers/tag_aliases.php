<?php
if (!check_perms('users_mod')) { error(403);
}

show_header('Tag Aliases');

$orderby = ($_GET['order']) == "badtags" ? "BadTag" : "AliasTag";        

if (isset($_POST['newalias'])) {
    $badtag = mysql_escape_string($_POST['badtag']);
    $aliastag = mysql_escape_string($_POST['aliastag']);

    $DB -> query("INSERT INTO tag_aliases (BadTag, AliasTag) VALUES ('$badtag', '$aliastag')");

}

if (isset($_POST['changealias'])) {
    $aliasid = $_POST['aliasid'];
    $badtag = mysql_escape_string($_POST['badtag']);
    $aliastag = mysql_escape_string($_POST['aliastag']);

    if ($_POST['save']) {
        $DB -> query("UPDATE tag_aliases SET BadTag = '$badtag', AliasTag = '$aliastag' WHERE ID = '$aliasid' ");
    }
    if ($_POST['delete']) {
        $DB -> query("DELETE FROM tag_aliases WHERE ID = '$aliasid'");
    }
}
?>
<div class="header">
	<h2>Tag Aliases</h2>
	<div class="linkbox">
	        [<a href="tools.php?action=tag_aliases&amp;order=goodtags">Sort by Good Tags</a>]
	        [<a href="tools.php?action=tag_aliases&amp;order=badtags">Sort by Bad Tags</a>]
    </div>
</div>
<table width="100%">
	<tr class="colhead">
		<td>Tag</td>
		<td>Renamed From</td>
		<td>Submit</td>
	</tr>
	<tr/>
	<tr>
		<form method="post">
			<input type="hidden" name="newalias" value="1" />
			<td>
			<input type="text" name="aliastag"/>
			</td>
			<td>
			<input type="text" name="badtag"/>
			</td>
			<td>
			<input type="submit" value="Add Alias"/>
			</td>
		</form>
	</tr>
	<?
$DB->query("SELECT ID,BadTag,AliasTag FROM tag_aliases ORDER BY " . $orderby);
while (list($ID, $BadTag, $AliasTag) = $DB -> next_record()) {
	?>
	<tr>
		<form method="post">
			<input type="hidden" name="changealias" value="1" />
			<input type="hidden" name="aliasid" value="<?=$ID?>" />
			<td>
			<input type="text" name="aliastag" value="<?=$AliasTag?>"/>
			</td>
			<td>
			<input type="text" name="badtag" value="<?=$BadTag?>"/>
			</td>
			<td>
			<input type="submit" name="save" value="Save Alias"/>
			<input type="submit" name="delete" value="Delete Alias"/>
			</td>
		</form>
	</tr>
	<? }?>
</table>
<?
show_footer();
?>
