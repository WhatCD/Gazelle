<?
/************************************************************************
||------------|| User passkey history page ||--------------------------||

This page lists previous passkeys a user has used on the site. It gets
called if $_GET['action'] == 'passkey'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

$UserID = $_GET['userid'];
if (!is_number($UserID)) { error(404); }

$DB->query("SELECT um.Username, p.Level AS Class FROM users_main AS um LEFT JOIN permissions AS p ON p.ID=um.PermissionID WHERE um.ID = ".$UserID);
list($Username, $Class) = $DB->next_record();

if(!check_perms('users_view_keys', $Class)) { 
	error(403);
}

show_header("PassKey history for $Username");

$DB->query("SELECT 
	OldPassKey, 
	NewPassKey, 
	ChangeTime,
	ChangerIP
	FROM users_history_passkeys
	WHERE UserID=$UserID
	ORDER BY ChangeTime DESC");

?>
<h2>Passkey history for <a href="/user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
<table width="100%">
	<tr class="colhead">
		<td>Old</td>
		<td>New</td>
		<td>Changed</td>
		<td>IP [<a href="/userhistory.php?action=ips&userid=<?=$UserID?>">H</a>]</td>
	</tr>
<? while(list($OldPassKey, $NewPassKey, $ChangeTime, $ChangerIP) = $DB->next_record()){ ?>
	<tr class="rowa">
		<td><?=display_str($OldPassKey)?></td>
		<td><?=display_str($NewPassKey)?></td>
		<td><?=time_diff($ChangeTime)?></td>
		<td><?=display_str($ChangerIP)?> [<a href="user.php?action=search&ip_history=on&ip=<?=display_str($ChangerIP)?>" title="Search">S</a>]<br /><?=display_str(gethostbyip($ChangerIP))?></td>
	</tr>
<? } ?>
</table>
<? show_footer(); ?>
