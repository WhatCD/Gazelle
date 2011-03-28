<?
if(isset($_GET['userid']) && check_perms('users_view_invites')){
	if(!is_number($_GET['userid'])){ error(403); }
	
	$UserID=$_GET['userid'];
	$Sneaky = true;
} else {
	if(!$UserCount = $Cache->get_value('stats_user_count')){
		$DB->query("SELECT COUNT(ID) FROM users_main WHERE Enabled='1'");
		list($UserCount) = $DB->next_record();
		$Cache->cache_value('stats_user_count', $UserCount, 0);
	}
	
	$UserID = $LoggedUser['ID'];
	$Sneaky = false;
}

list($UserID, $Username, $PermissionID) = array_values(user_info($UserID));

include(SERVER_ROOT.'/classes/class_invite_tree.php');
$Tree = new INVITE_TREE($UserID);

show_header($Username.' &gt; Invites &gt; Tree');
?>
<div class="thin">
	<h2><?=format_username($UserID,$Username)?> &gt; <a href="user.php?action=invite&amp;userid=<?=$UserID?>">Invites</a> &gt; Tree</h2>
	<div class="box pad">
<?	$Tree->make_tree(); ?>
	</div>
</div>
<? show_footer(); ?>