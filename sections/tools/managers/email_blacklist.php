<?
define('EMAILS_PER_PAGE', 25);
if (!check_perms('users_view_email')) {
	error(403);
}
list ($Page, $Limit) = Format::page_limit(EMAILS_PER_PAGE);

View::show_header('Manage email blacklist');
$Where = "";
if (!empty($_POST['email'])) {
	$Email = db_string($_POST['email']);
	$Where .= " WHERE Email LIKE '%$Email%'";
}
if (!empty($_POST['comment'])) {
	$Comment = db_string($_POST['comment']);
	if (!empty($Where)) {
		$Where .= " AND";
	} else {
		$Where .= " WHERE";
	}
	$Where .= " Comment LIKE '%$Comment%'";
}
$DB->query("
	SELECT
		SQL_CALC_FOUND_ROWS
		ID,
		UserID,
		Time,
		Email,
		Comment
	FROM email_blacklist
	$Where
	ORDER BY Time DESC
	LIMIT $Limit");
$Results = $DB->to_array(false, MYSQLI_ASSOC, false);
$DB->query('SELECT FOUND_ROWS()');
list ($NumResults) = $DB->next_record();
?>
<div class="header">
	<h2>Email Blacklist</h2>
</div>
<br />
<form action="tools.php" method="post">
	<input type="hidden" name="action" value="email_blacklist" />
	<input type="email" name="email" size="30" placeholder="Email" />
	<input type="search" name="comment" size="60" placeholder="Comment" />
	<input type="submit" value="Search" />
</form>
<div class="linkbox pager">
	<br />
<?
	$Pages = Format::get_pages($Page, $NumResults, TOPICS_PER_PAGE, 9);
	echo $Pages;
?>
</div>
<table>
	<tr class="colhead">
		<td>Email address</td>
		<td>Comment</td>
		<td>Date added</td>
		<td>Submit</td>
	</tr>
	<tr class="colhead">
		<td colspan="4">Add email address or domain to blacklist</td>
	</tr>
	<tr class="rowa">
		<form class="add_form" name="email_blacklist" action="tools.php" method="post">
			<input type="hidden" name="action" value="email_blacklist_alter" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<td><input type="text" name="email" size="30" /></td>
			<td colspan="2"><input type="text" name="comment" size="60" /></td>
			<td><input type="submit" value="Create" /></td>
		</form>
	</tr>
<?
	foreach ($Results as $Result) {
?>
	<tr>
		<form class="manage_form" name="email_blacklist" action="tools.php" method="post">
			<td>
				<input type="hidden" name="action" value="email_blacklist_alter" />
				<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
				<input type="hidden" name="id" value="<?=$Result['ID']?>" />
				<input type="email" name="email" value="<?=display_str($Result['Email'])?>" size="30" />
			</td>
			<td><input type="text" name="comment" value="<?=display_str($Result['Comment'])?>" size="60" /></td>
			<td><?=Users::format_username($Result ['UserID'], false, false, false)?><br /><?=time_diff($Result ['Time'], 1)?></td>
			<td>
				<input type="submit" name="submit" value="Edit" />
				<input type="submit" name="submit" value="Delete" />
			</td>
		</form>
	</tr>
<?	} ?>
</table>
<div class="linkbox pager">
	<br />
	<?=$Pages?>
</div>
<? View::show_footer(); ?>
