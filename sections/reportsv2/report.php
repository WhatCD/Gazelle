<?
/*
 * This is the frontend of reporting a torrent, it's what users see when
 * they visit reportsv2.php?id=xxx
 */

//If we're not coming from torrents.php, check we're being returned because of an error.
if(!isset($_GET['id']) || !is_number($_GET['id'])) {
	if(!isset($Err)) {
		error(404);
	}
} else {
	$TorrentID = $_GET['id'];
	$DB->query("SELECT tg.CategoryID FROM torrents_group AS tg LEFT JOIN torrents AS t ON t.GroupID=tg.ID WHERE t.ID=".$_GET['id']);
	list($CategoryID) = $DB->next_record();
}

show_header('Report', 'reportsv2');
?>

<div class="thin">
	<h2>Report a torrent</h2>

	<form action="reportsv2.php?action=takereport" enctype="multipart/form-data" method="post" id="report_table">
		<div>
			<input type="hidden" name="submit" value="true" />
			<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
			<input type="hidden" name="torrentid" value="<?=$TorrentID?>" />
			<input type="hidden" name="categoryid" value="<?=$CategoryID?>" />
		</div>
		<table>
			<tr>
				<td class="label">Reason :</td>
				<td>
					<select id="type" name="type" onchange="ChangeReportType()">
<?
	if (!empty($Types[$CategoryID])) {
		$TypeList = $Types['master'] + $Types[$CategoryID];
		$Priorities = array();
		foreach ($TypeList as $Key => $Value) {
			$Priorities[$Key] = $Value['priority'];
		}
		array_multisort($Priorities, SORT_ASC, $TypeList);
	} else {
		$TypeList = $Types['master'];
	}
	foreach($TypeList as $Type => $Data) {
?>
						<option value="<?=$Type?>"><?=$Data['title']?></option>
<? } ?>
					</select>
				</td>
			</tr>
		</table>
			
		<h3>Reporting guidelines</h3>
		<div class="box pad">
			<p>Fields that contain lists of values (for example, listing more than one track number) should be separated by a space.</p>
			<br />
			<p><strong>Following the below report type specific guidelines will help the moderators deal with your report in a timely fashion. </strong></p>
			<br />
			
			<div id="dynamic_form">
				<? 
				/*
				 * THIS IS WHERE SEXY AJAX COMES IN
				 * The following malarky is needed so that if you get sent back here the fields are filled in
				 */ 
				?>
				<input id="sitelink" type="hidden" name="sitelink" size="50" value="<?=(!empty($_POST['sitelink']) ? display_str($_POST['sitelink']) : '')?>" />
				<input id="image" type="hidden" name="image" size="50" value="<?=(!empty($_POST['image']) ? display_str($_POST['image']) : '')?>" />
				<input id="track" type="hidden" name="track" size="8" value="<?=(!empty($_POST['track']) ? display_str($_POST['track']) : '')?>" />
				<input id="link" type="hidden" name="link" size="50" value="<?=(!empty($_POST['link']) ? display_str($_POST['link']) : '')?>" />
				<input id="extra" type="hidden" name="extra" value="<?=(!empty($_POST['extra']) ? display_str($_POST['extra']) : '')?>" />

				<script type="text/javascript">ChangeReportType();</script>
			</div>
			
		</div>
	<input type="submit" value="Submit report" />
	</form>
</div>
<?
show_footer();
?>

