<?
if(!check_perms('site_recommend_own') && !check_perms('site_manage_recommendations')){
	error(403);
}
show_header('Recommendations');

$DB->query("SELECT 
	tr.GroupID,
	tr.UserID,
	u.Username,
	tg.Name,
	tg.ArtistID,
	ag.Name
	FROM torrents_recommended AS tr
	JOIN torrents_group AS tg ON tg.ID=tr.GroupID
	LEFT JOIN artists_group AS ag ON ag.ArtistID=tg.ArtistID
	LEFT JOIN users_main AS u ON u.ID=tr.UserID
	ORDER BY tr.Time DESC
	LIMIT 10
	");
?>
<div class="thin">
	<div class="box" id="recommended">
		<div class="head colhead_dark"><strong>Recommendations</strong></div>
<?		if(!in_array($LoggedUser['ID'], $DB->collect('UserID'))){ ?>
		<form action="tools.php" method="post" class="pad">
			<input type="hidden" name="action" value="recommend_add" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<table cellpadding="6" cellspacing="1" border="0" class="border" width="100%">
				<tr>
					<td rowspan="2" class="label"><strong>Add Recommendation:</strong></td>
					<td>Link to a torrent group on site. E.g. <strong>http://<?=NONSSL_SITE_URL?>/torrents.php?id=10000</strong></td>
				</tr>
				<tr>
					<td>
						<input type="text" name="url" size="50" />
						<input type="submit" value="Add recommendation" />
					</td>
				</tr>
			</table>
		</form>
<?		} ?>
		<ul class="nobullet">
<?
	while(list($GroupID, $UserID, $Username, $GroupName, $ArtistID, $ArtistName)=$DB->next_record()) {
?>
			<li>
				<strong><?=format_username($UserID, $Username)?></strong>
<?		if($ArtistID){ ?> 
				- <a href="artist.php?id=<?=$ArtistID?>"><?=$ArtistName?></a>
<?		} ?> 
				- <a href="torrents.php?id=<?=$GroupID?>"><?=$GroupName?></a>
<?		if(check_perms('site_manage_recommendations') || $UserID == $LoggedUser['ID']){ ?>
				<a href="tools.php?action=recommend_alter&amp;groupid=<?=$GroupID?>">[Delete]</a>
<?		} ?> 
			</li>
<?	} ?>
		</ul>
	</div>
</div>
<? show_footer(); ?>
