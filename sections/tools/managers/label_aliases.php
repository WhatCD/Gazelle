<?php
/*
if (!check_perms('torrents_edit') || $LoggedUser['DisableWiki']) {
	error(403);
}
*/

if (!check_perms('users_mod') && !$LoggedUser['ExtraClasses'][DELTA_TEAM]) {
	error(403);
}

View::show_header('Label Aliases');

$OrderBy = $_GET['order'] === 'BadLabels' ? 'BadLabel' : 'AliasLabel';
/*
$LabelID = (int)$_GET['id'];
$LabelNameSQL = '';
//TODO join with labels table to get label name
if (!empty($LabelID)) {
	$DB->query("
		SELECT name
		FROM labels
		WHERE ID = '$LabelID'");
	if ($DB->has_results()) {
		list($LabelName) = $DB->next_record();
	}
	$LabelNameSQL = " WHERE AliasLabel = '$LabelName'";
}
*/

if (isset($_POST['newalias'])) {
	$BadLabel = db_string($_POST['BadLabel']);
	$AliasLabel = db_string($_POST['AliasLabel']);

	$DB->query("
		INSERT INTO label_aliases (BadLabel, AliasLabel)
		VALUES ('$BadLabel', '$AliasLabel')");
}

if (isset($_POST['changealias']) && is_number($_POST['aliasid'])) {
	$AliasID = $_POST['aliasid'];
	$BadLabel = db_string($_POST['BadLabel']);
	$AliasLabel = db_string($_POST['AliasLabel']);

	if ($_POST['save']) {
		$DB->query("
			UPDATE label_aliases
			SET BadLabel = '$BadLabel', AliasLabel = '$AliasLabel'
			WHERE ID = '$AliasID' ");
	}
	if ($_POST['delete']) {
		$DB->query("
			DELETE FROM label_aliases
			WHERE ID = '$AliasID'");
	}
}
?>
<div class="header">
	<h2>Label Aliases<?=($LabelName ? " for <a href=\"labels.php?id=$LabelID\">$LabelName</a>" : '')?></h2>
	<div class="linkbox">
		<a href="tools.php?action=label_aliases&amp;order=GoodLabels" class="brackets">Sort by good labels</a>
		<a href="tools.php?action=label_aliases&amp;order=BadLabels" class="brackets">Sort by bad labels</a>
	</div>
</div>
<table width="100%">
	<tr class="colhead">
		<td>Label</td>
		<td>Renamed from</td>
		<td>Submit</td>
	</tr>
	<tr />
	<tr>
		<form method="post" action="">
			<input type="hidden" name="newalias" value="1" />
			<td>
				<input type="text" name="AliasLabel" />
			</td>
			<td>
				<input type="text" name="BadLabel" />
			</td>
			<td>
				<input type="submit" value="Add alias" />
			</td>
		</form>
	</tr>
<?
$DB->query("
	SELECT ID, BadLabel, AliasLabel
	FROM label_aliases
	$LabelNameSQL
	ORDER BY $OrderBy");
while (list($ID, $BadLabel, $AliasLabel) = $DB->next_record()) {
?>
	<tr>
		<form method="post" action="">
			<input type="hidden" name="changealias" value="1" />
			<input type="hidden" name="aliasid" value="<?=$ID?>" />
			<td>
				<input type="text" name="AliasLabel" value="<?=$AliasLabel?>" />
			</td>
			<td>
				<input type="text" name="BadLabel" value="<?=$BadLabel?>" />
			</td>
			<td>
				<input type="submit" name="save" value="Save alias" />
				<input type="submit" name="delete" value="Delete alias" />
			</td>
		</form>
	</tr>
<?
}
?>
</table>
<? View::show_footer(); ?>
