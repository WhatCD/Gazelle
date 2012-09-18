<?php
if (!check_perms('users_view_invites')) { error(403);
}

show_header('Fishy Invites');


if (isset($_POST['newcountry'])) {
    $country = mysql_escape_string(strtoupper($_POST['country']));
    $DB -> query("INSERT INTO country_invites (Country) VALUES ('$country')");

}

if (isset($_POST['changecountry'])) {
    $id = $_POST['id'];
    $country = mysql_escape_string(strtoupper($_POST['country']));

    if ($_POST['save']) {
        $DB -> query("UPDATE country_invites SET Country = '$country' WHERE ID = '$id'");
    }
    if ($_POST['delete']) {
        $DB -> query("DELETE FROM country_invites WHERE ID = '$id'");
    }
}
?>

<div class="header">
	<h2>IRC highlight on (fishy) invites sent to countries</h2>
</div>
<table width="100%">
	<tr class="colhead">
		<td>Country</td>
		<td>Submit</td>
	</tr>
	<tr>
		<form class="add_form" name="countries" method="post">
			<input type="hidden" name="newcountry" value="1" />
			<td>
				<input type="text" name="country" />
			</td>
			<td>
				<input type="submit" value="Add Country" />
			</td>
		</form>
	</tr>
	<?
$DB->query("SELECT ID, Country FROM country_invites ORDER BY Country");
while (list($ID, $Country) = $DB->next_record()) {
	?>
	<tr>
		<form class="manage_form" name="countries" method="post">
			<input type="hidden" name="changecountry" value="1" />
			<input type="hidden" name="id" value="<?=$ID?>" />
			<td>
				<input type="text" name="country" value="<?=$Country?>" />
			</td>
			<td>
				<input type="submit" name="save" value="Save Country" />
				<input type="submit" name="delete" value="Delete Country" />
			</td>
		</form>
	</tr>
	<? }?>
</table>
<? show_footer(); ?>
