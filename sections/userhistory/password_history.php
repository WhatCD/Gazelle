<?
/************************************************************************
||------------|| Password reset history page ||------------------------||

This page lists password reset IP and Times a user has made on the site. 
It gets called if $_GET['action'] == 'password'.

It also requires $_GET['userid'] in order to get the data for the correct
user.

************************************************************************/

if(!check_perms('users_view_keys')) { error(403); }

$UserID = $_GET['userid'];
if (!is_number($UserID)) { error(404); }

$DB->query("SELECT UserName FROM users_main WHERE ID = $UserID");
list($Username) = $DB->next_record();

show_header("Password reset history for $Username");

$DB->query("SELECT 
	ChangeTime,
	ChangerIP
	FROM users_history_passwords
	WHERE UserID=$UserID
	ORDER BY ChangeTime DESC");

?>
<h2>Password reset history for <a href="/user.php?id=<?=$UserID?>"><?=$Username?></a></h2>
<table width="100%">
	<tr class="colhead">
		<td>Changed</td>
		<td>IP [<a href="/userhistory.php?action=ips&userid=<?=$UserID?>">H</a>]</td>
	</tr>
<? while(list($ChangeTime, $ChangerIP) = $DB->next_record()){ ?>
	<tr class="rowa">
		<td><?=time_diff($ChangeTime)?></td>
		<td><?=display_str($ChangerIP)?> [<a href="/user.php?action=search&ip_history=on&ip=<?=display_str($ChangerIP)?>" title="Search">S</a>]<br /><?=get_host($ChangerIP)?></td>
	</tr>
<? } ?>
</table>
<? show_footer(); ?>
