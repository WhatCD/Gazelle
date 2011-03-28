<?
/*
 * This page is to outline all the sexy views build into reports v2.
 * It's used as the main page as it also lists the current reports by type
 * and also current in progress reports by staff member.
 * All the different views are self explanatory by their names.
 */
if(!check_perms('admin_reports')){
	error(403);
}

show_header('Reports V2!', 'reportsv2');
include('header.php');

//Grab owners ID, just for examples
$DB->query("SELECT ID, Username FROM users_main ORDER BY ID ASC LIMIT 1");
list($OwnerID, $Owner) = $DB->next_record();
$Owner = display_str($Owner);

?>
<h2>Reports v2 Information!</h2>
<br />
<div class="box pad thin" style="padding: 0px 0px 0px 20px; width: 70%; margin-left: auto; margin-right: auto">
	<table><tr><td style="width: 50%;">
<?
$DB->query("SELECT um.ID, um.Username, COUNT(r.ID) AS Reports FROM reportsv2 AS r JOIN users_main AS um ON um.ID=r.ResolverID WHERE r.LastChangeTime > NOW() - INTERVAL 24 HOUR GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<strong>Reports resolved in the last 24 hours</strong>
		<table class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($UserID, $Username, $Reports) = $Result;
?>
			<tr>
				<td><a href="reportsv2.php?view=resolver&amp;id=<?=$UserID?>"><?=$Username?></a></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		<br />
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reportsv2 AS r JOIN users_main AS um ON um.ID=r.ResolverID WHERE r.LastChangeTime > NOW() - INTERVAL 1 WEEK GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<strong>Reports resolved in the last week</strong>
		<table class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		<br />
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reportsv2 AS r JOIN users_main AS um ON um.ID=r.ResolverID WHERE r.LastChangeTime > NOW() - INTERVAL 1 MONTH GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<strong>Reports resolved in the last month</strong>
		<table class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		<br />
<?
$DB->query("SELECT um.Username, COUNT(r.ID) AS Reports FROM reportsv2 AS r JOIN users_main AS um ON um.ID=r.ResolverID GROUP BY r.ResolverID ORDER BY Reports DESC");
$Results = $DB->to_array();
?>
		<strong>Reports resolved since reportsv2 (2009-07-27)</strong>
		<table class="border">
			<tr>
				<td class="head colhead_dark">Username</td>
				<td class="head colhead_dark">Reports</td>
			</tr>
<? foreach($Results as $Result) {
	list($Username, $Reports) = $Result;
?>
			<tr>
				<td><?=$Username?></td>
				<td><?=$Reports?></td>
			</tr>
<? } ?>
		</table>
		<br />
		<h3>Different view modes by person</h3>
		<br />
		<strong>By ID of torrent reported.</strong>
		<ul>
			<li>
				Reports of torrents with ID = 1
			</li>
			<li>
				<a href="reportsv2.php?view=torrent&amp;id=1">http://<?=NONSSL_SITE_URL?>/reportsv2.php?view=torrent&amp;id=1</a>
			</li>
		</ul>
		<br />
		<strong>By GroupID of torrent reported.</strong>
		<ul>
			<li>
				Reports of torrents within the group with ID = 1
			</li>
			<li>
				<a href="reportsv2.php?view=group&amp;id=1">http://<?=NONSSL_SITE_URL?>/reportsv2.php?view=group&amp;id=1</a>
			</li>
		</ul>
		<br />
		<strong>By Report ID.</strong>
		<ul>
			<li>
				The report with ID = 1
			</li>
			<li>
				<a href="reportsv2.php?view=report&amp;id=1">http://<?=NONSSL_SITE_URL?>/reportsv2.php?view=report&amp;id=1</a>
			</li>
		</ul>
		<br />
		<strong>By Reporter ID.</strong>
		<ul>
			<li>
				Reports created by <?=$Owner?>
			</li>
			<li>
				<a href="reportsv2.php?view=reporter&amp;id=<?=$OwnerID?>">http://<?=NONSSL_SITE_URL?>/reportsv2.php?view=reporter&amp;id=<?=$OwnerID?></a>
			</li>
		</ul>
		<br />
		<strong>By uploader ID.</strong>
		<ul>
			<li>
				Reports for torrents uploaded by <?=$Owner?>
			</li>
			<li>
				<a href="reportsv2.php?view=uploader&amp;id=<?=$OwnerID?>">http://<?=NONSSL_SITE_URL?>/reportsv2.php?view=uploader&amp;id=<?=$OwnerID?></a>
			</li>
		</ul>
		<br />
		<strong>By resolver ID.</strong>
		<ul>
			<li>
				Reports for torrents resolved by <?=$Owner?>
			</li>
			<li>
				<a href="reportsv2.php?view=resolver&amp;id=<?=$OwnerID?>">http://<?=NONSSL_SITE_URL?>/reportsv2.php?view=resolver&amp;id=<?=$OwnerID?></a>
			</li>
		</ul>
		<br /><br />
		<strong>For browsing anything more complicated than these, use the search feature.</strong>
	</td>
	<td style="vertical-align: top;">
<?
	$DB->query("SELECT r.ResolverID,
						um.Username, 
						COUNT(r.ID) AS Count,
						COUNT(tasted.Tasted) AS Tasted
				FROM reportsv2 AS r
				LEFT JOIN users_main AS um ON r.ResolverID=um.ID
				LEFT JOIN torrents AS tasted ON tasted.ID=r.TorrentID AND tasted.Tasted = '1'
				WHERE r.Status = 'InProgress'
				GROUP BY r.ResolverID");
	$Staff = $DB->to_array();
?>
		<strong>Currently assigned reports by staff member</strong>
		<table>
			<tr>
				<td class="colhead">Staff member</td>
				<td class="colhead">Current Count</td>
				<td class="colhead">Tasted</td>
			</tr>
		
	<?	
		foreach($Staff as $Array) {	?>
			<tr>
				<td>
					<a href="reportsv2.php?view=staff&amp;id=<?=$Array['ResolverID']?>"><?=display_str($Array['Username'])?>'s reports</a>
				</td>
				<td><?=$Array['Count']?></td>
				<td>
					<a href="reportsv2.php?view=tasted&amp;id=<?=$Array['ResolverID']?>"><?=display_str($Array['Tasted'])?></a>
				</td>
			</tr>
	<?	
		}
	?>
		</table>
		<br />
		<h3>Different view modes by report type</h3>
<?
	$DB->query("SELECT 	r.Type,
						COUNT(r.ID) AS Count
				FROM reportsv2 AS r 
				WHERE r.Status='New'
				GROUP BY r.Type");
	$Current = $DB->to_array();
	if(!empty($Current)) {
?>
		<table>
			<tr>
				<td class="colhead">Type</td>
				<td class="colhead">Current Count</td>
			</tr>
<?
		foreach($Current as $Array) {
			//Ugliness
			foreach($Types as $Category) {
				if(!empty($Category[$Array['Type']])) {
					$Title = $Category[$Array['Type']]['title'];
					break;
				}
			}
?>
			<tr>
				<td>
					<a href="reportsv2.php?view=type&amp;id=<?=display_str($Array['Type'])?>"><?=display_str($Title)?></a>
				</td>
				<td>
					<?=$Array['Count']?>
				</td>
			</tr>
<?
		}
	}
?>
		</table>
	</td></tr></table>
</div>
<?
show_footer();
?>
