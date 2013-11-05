<?
/************************************************************************
||------------|| Password reset history page ||------------------------||

This page lists password reset IP and Times a user has made on the site.
It gets called if $_GET['action'] == 'password'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

$UserID = $_GET['userid'];
if (!is_number($UserID)) {
	error(404);
}

$DB->query("
	SELECT
		um.Username,
		p.Level AS Class
	FROM users_main AS um
		LEFT JOIN permissions AS p ON p.ID = um.PermissionID
	WHERE um.ID = $UserID");
list($Username, $Class) = $DB->next_record();

if (!check_perms('users_view_keys', $Class)) {
	error(403);
}

View::show_header("Password reset history for $Username");

$DB->query("
	SELECT
		ChangeTime,
		ChangerIP
	FROM users_history_passwords
	WHERE UserID = $UserID
	ORDER BY ChangeTime DESC");

?>
<div class="header">
	<h2>Password reset history for <a href="/user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
</div>
<table width="100%">
	<tr class="colhead">
		<td>Changed</td>
		<td>IP <a href="/userhistory.php?action=ips&amp;userid=<?=$UserID?>" class="brackets">H</a></td>
	</tr>
<? while (list($ChangeTime, $ChangerIP) = $DB->next_record()) { ?>
	<tr class="rowa">
		<td><?=time_diff($ChangeTime)?></td>
		<td><?=display_str($ChangerIP)?> <a href="/user.php?action=search&amp;ip_history=on&amp;ip=<?=display_str($ChangerIP)?>" class="brackets tooltip" title="Search">S</a><br /><?=Tools::get_host_by_ajax($ChangerIP)?></td>
	</tr>
<? } ?>
</table>
<? View::show_footer(); ?>
